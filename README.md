## FrankenPHP Demo: File mover

This is a demo application using FrankenPHP for moving stale files and sending notifications.
Check out the [build-static-binary.yml](.github/workflows/build-static-binary.yml) to see how to
generate a new binary for each new tag in the git repository.

Refer to the official [FrankenPHP Documentation](https://frankenphp.dev/docs/) for more information.

Additionally, I wrote a blog post about [releasing PHP Apps as Binaries](https://blog.bitexpert.de/blog/frankenphp-gitlab-ci).

## Demo usage of binary

After downloading the binary from the latest release, put it into the project root. 
You should be able to execute the following commands to see the binary in action

```shell
# Spin up Mailcatcher
$ docker compose up database mailer -d
# Create files with modified timestamp
$ touch -d "-5 minutes" tests/fixtures/source/new_file
$ touch -d "-3600 minutes" tests/fixtures/source/stale_file
# demo binary is called file-mover
# Send notification for stale_file
$ ./file-mover php-cli bin/console app:notify-stale-files source 60
# Move stale_file to destination folder
$ ./file-mover php-cli bin/console app:move-files tests/fixtures/source tests/fixtures/destination 60
# Start up the Web App:
$ sudo ./file-mover php-server --domain=localhost
```

Mailer:
http://localhost:8025/

Web App:
http://localhost
