<?php
require_once __DIR__.'/vendor/autoload.php';

use Studypet\Tools\BracketChecker;

$ip_address = "0.0.0.0";
$port = "10001";

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket, $ip_address, $port);
socket_listen($socket, 100);

$fork_need = true;
$connections = [];

do {
    if ($fork_need) {
        $pid = pcntl_fork();
        $fork_need = false;
    }

    if ($pid == -1) {

        throw new Error("Can't create new process :(");

    } elseif ($pid) {

        $server = true;
        $connections[$pid] = true;

        pcntl_signal(SIGUSR1, function ($signo) use (&$fork_need) {
            if ($signo == SIGUSR1) {
                $fork_need = true;
            }
        });

        pcntl_signal(SIGTERM, function ($signo) use (&$socket, $connections) {
            if ($signo == SIGTERM) {
                foreach ($connections as $cpid => $connected) {
                    print "Shutting down connection {$cpid}...\n";
                    posix_kill($cpid, SIGKILL);
                }
                sleep(3);
                print "Server is shutting down... By... :) \n";
                socket_close($socket);
                exit (0);
            }
        });
        pcntl_signal_dispatch();


        while ($s_pid = pcntl_waitpid(-1, $status, WNOHANG)) {
            if ($s_pid == -1) {
                $connections = [];
                break;
            } else {
                unset($connections[$s_pid]);
                print sprintf("%s destroyed\n", $s_pid);
            }
        }

    } else {

        $server = false;
        $cpid = getmypid();
        $ppid = posix_getppid();

        $msgsock = socket_accept($socket);

        posix_kill($ppid, SIGUSR1);

        print sprintf("New client: %s\n", $cpid);

        pcntl_signal(SIGTERM, function () use ($cpid, $msgsock) {

            $msg = "By {$cpid} :)\n";

            socket_write($msgsock, $msg, strlen($msg));
            socket_close($msgsock);

            print sprintf("Client %s disconnected\n", $cpid);

            exit (0);

        });

        $msg = "\nWelcome to te PHP Test Server\n";
        socket_write($msgsock, $msg, strlen($msg));

        do {

            pcntl_signal_dispatch();

            $msg = "RDY\n";
            socket_write($msgsock, $msg, strlen($msg));

            $buffer = socket_read($msgsock, 2048, PHP_BINARY_READ);
            $buffer = trim($buffer);

            print sprintf("Client %s > %s\n", $cpid, $buffer);

            if($buffer == 'quit') {
                break;
            } elseif ($buffer == 'shutdown') {
                posix_kill($ppid, SIGTERM);
                break;
            } else {
                try {
                    $msg = BracketChecker::check($buffer) ? "VALID\n" : "INVALID\n";
                } catch (Exception $e) {
                    $msg = "Error: {$e->getMessage()}\n";
                }
                socket_write($msgsock, $msg, strlen($msg));
            }

        } while(true);

        posix_kill($cpid, SIGTERM);
        pcntl_signal_dispatch();

    }

} while($server);

socket_close($socket);

