<?php
namespace App\Console\Command;

use App\Entity;
use App\Utilities;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class Backup extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:backup')
            ->setDescription(
                __('Back up the AzuraCast database and statistics (and optionally media).')
            )
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                __('The absolute (or relative to /var/azuracast/backups) path to generate the backup.'),
                ''
            )
            ->addOption(
                'exclude-media',
                null,
                InputOption::VALUE_NONE,
                __('Exclude media from the backup.')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start_time = microtime(true);

        $destination_path = $input->getArgument('path');
        if (empty($destination_path)) {
            $destination_path = 'manual_backup_'.gmdate('Ymd_Hi').'.zip';
        }
        if ('/' !== $destination_path[0]) {
            $destination_path = \App\Sync\Task\Backup::BASE_DIR.'/'.$destination_path;
        }

        $include_media = !(bool)$input->getOption('exclude-media');

        $files_to_backup = [];

        $io = new SymfonyStyle($input, $output);
        $io->title(__('AzuraCast Backup'));
        $io->writeln(__('Please wait while a backup is generated...'));

        // Create temp directories
        $io->section(__('Creating temporary directories...'));

        $tmp_dir_mariadb = '/tmp/azuracast_backup_mariadb';
        if (!mkdir($tmp_dir_mariadb) && !is_dir($tmp_dir_mariadb)) {
            $io->error(__('Directory "%s" was not created', $tmp_dir_mariadb));
            return 1;
        }

        $tmp_dir_influxdb = '/tmp/azuracast_backup_influxdb';
        if (!mkdir($tmp_dir_influxdb) && !is_dir($tmp_dir_influxdb)) {
            $io->error(__('Directory "%s" was not created', $tmp_dir_influxdb));
            return 1;
        }

        $io->newLine();

        // Back up MariaDB
        $io->section(__('Backing up MariaDB...'));

        $path_db_dump = $tmp_dir_mariadb.'/db.sql';

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);
        $conn = $em->getConnection();

        $process = $this->passThruProcess(
            $io,
            'mysqldump --host=$DB_HOST --user=$DB_USERNAME --password=$DB_PASSWORD --add-drop-table --default-character-set=UTF8MB4 $DB_DATABASE > $DB_DEST',
            $tmp_dir_mariadb,
            [
                'DB_HOST'     => $conn->getHost(),
                'DB_DATABASE' => $conn->getDatabase(),
                'DB_USERNAME' => $conn->getUsername(),
                'DB_PASSWORD' => $conn->getPassword(),
                'DB_DEST'     => $path_db_dump,
            ]
        );

        $files_to_backup[] = $path_db_dump;
        $io->newLine();

        // Back up InfluxDB
        $io->section(__('Backing up InfluxDB...'));

        /** @var \InfluxDB\Database $influxdb */
        $influxdb = $this->get(\InfluxDB\Database::class);
        $influxdb_client = $influxdb->getClient();

        $this->passThruProcess($io, [
            'influxd',
            'backup',
            '-database', 'stations',
            '-portable',
            '-host',
            $influxdb_client->getHost().':8088',
            $tmp_dir_influxdb,
        ], $tmp_dir_influxdb);

        $files_to_backup[] = $tmp_dir_influxdb;
        $io->newLine();

        // Include station media if specified.
        if ($include_media) {
            $stations = $em->createQuery(/** @lang DQL */'SELECT s FROM App\Entity\Station s')
                ->execute();

            foreach($stations as $station) {
                /** @var Entity\Station $station */

                $media_dir = $station->getRadioMediaDir();
                if (!in_array($media_dir, $files_to_backup, true)) {
                    $files_to_backup[] = $media_dir;
                }

                $art_dir = $station->getRadioAlbumArtDir();
                if (!in_array($art_dir, $files_to_backup, true)) {
                    $files_to_backup[] = $art_dir;
                }
            }
        }

        // Compress backup files.
        $io->section(__('Creating backup archive...'));

        // Strip leading slashes from backup paths.
        $files_to_backup = array_map(function($val) {
            if (0 === strpos($val, '/')) {
                return substr($val, 1);
            }
            return $val;
        }, $files_to_backup);

        $file_ext = strtolower(pathinfo($destination_path, \PATHINFO_EXTENSION));

        switch($file_ext) {
            case 'gz':
            case 'tgz':
                $this->passThruProcess($io, array_merge([
                    'tar',
                    'zcvf',
                    $destination_path
                ], $files_to_backup),'/');
                break;

            case 'zip':
            default:
                $dont_compress = ['.tar.gz', '.zip', '.jpg', '.mp3', '.ogg', '.flac', '.aac', '.wav'];

                $this->passThruProcess($io, array_merge([
                    'zip',
                    '-r',
                    '-n', implode(':', $dont_compress),
                    $destination_path
                ], $files_to_backup),'/');
                break;
        }

        $io->newLine();

        // Cleanup
        $io->section(__('Cleaning up temporary files...'));

        Utilities::rmdirRecursive($tmp_dir_mariadb);
        Utilities::rmdirRecursive($tmp_dir_influxdb);

        $io->newLine();

        $end_time = microtime(true);
        $time_diff = $end_time - $start_time;

        $io->success([
            __('Backup complete in %.2f seconds.'), $time_diff,
        ]);
        return 0;
    }

    protected function passThruProcess(SymfonyStyle $io, $cmd, $cwd = null, array $env = []): Process
    {
        set_time_limit(3600);

        if (is_array($cmd)) {
            $process = new Process($cmd, $cwd);
        } else {
            $process = Process::fromShellCommandline($cmd, $cwd);
        }

        $process->setTimeout(3500);
        $process->setIdleTimeout(60);

        $stdout = [];
        $stderr = [];

        $process->mustRun(function($type, $data) use ($process, $io, &$stdout, &$stderr) {
            if ($process::ERR === $type) {
                $io->getErrorStyle()->write($data);
                $stderr[] = $data;
            } else {
                $io->write($data);
                $stdout[] = $data;
            }
        }, $env);

        if (!empty($stderr)) {
            /** @var Logger $logger */
            $logger = $this->get(Logger::class);
            $logger->error(__('Backup process error'), [
                'cmd' => $cmd,
                'output' => $stderr,
            ]);
        }

        return $process;
    }
}
