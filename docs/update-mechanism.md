# Automatic Update-Mechanism

## Functionality

The updater provides a way to update Antragsgrün using a web-based interface without having to log use the command line or a SFTP-client. It updates both the code and the database.

Automatic updates are possible within a major version line like 3.x. This means, updating from a minor version like to 4.1.x to 4.2.x will be possible, while updating to a version 5 may or may not be.

Updates are provided as patches, which means only the changed files are downloaded and applied. All updates are signed to ensure code integrity. The [Sodium crypto library](https://download.libsodium.org/doc/) is used for signing (using polyfills for PHP-Versions < 7.2).

## Components

Three components are relevant:

- The central server providing a minimal API for querying for new versions. Given the current version of Antragsgrün, a link to the best possible update is returned.
- A notification in the regular Antragsgrün backend. This only indicates to the administrators if an update is available and is capable of activating the update mode.
- The actual update script. It tries to rely on as little external scripts as possible, especially not components that are subject to being updated, like the Yii-Framework.

## Update mode

The update script (``web/update.php``) is disabled by default. It is activated from the backend of Antragsgrün by adding a random secret key to the ``config.json``:

```json
{
    // "dbConnection": "...",
    "updateKey": "somerandomkey",
    // "...",
}
```

The key is only available for the admin of the site (and anyone with access to the config.json) and serves for athenticating the admin. It will be saved in a cookie when activating the update mode. If the cookie is lost and the update script is called, a simple form with the possibility to enter it again is presented. In this case, the admin has to check for the key in the config.json.

## Checking for updates

The API endpoint for querying updates is:

```
https://antragsgruen.de/updates/1.2.3
```

…with ``1.2.3``being the currently deployed version. The API returns an array of update-objects, like this:

```json
[
    {
        "type": "patch",
        "version": "1.2.4",
        "changelog": "Here comes a plain-text description of this update, including linebreaks.",
        "url": "https://antragsgruen.de/updates/1.2.3/1.2.4.zip",
        "filesize": 12345,
        "signature": "xxxxxxxxxx"
    }
]
```

Hint about the fields:

- ``type``: any of ``patch``, ``minor``, ``major``
- ``url``: this can be any URL, including other domains (like CDNs). The given value is just an illustration.
- ``signature``: a base64-encoded checksum (``sodium_crypto_generichash``) of the file content. This only serves to quickly check if the file has been downloaded correctly.

If the returned array contains multiple entries, there are possible updates available. This may be an update to the latest version within the same minor version *and* one to the next minor version. This will probably not be implemented at the beginning.

## Verifying update integrity

Verification is performed using the public-private-key signing mechanism of Sodium. The public key is included in the distribution of Antragsgrün, located at ``config/update-public.key``. The private key is only used to create the update files and not actually stored on the server.

Inside of the update ZIP-file is a file ``/update.json`` and ``/update.json.signature``. Using the content of the latter one and the Antragsgrün-bundled public key, the integrity of update.json can be checked using this command:

```php
if (sodium_crypto_sign_verify_detached($signature, $content_of_update_json, $antragsgruen_public_key) !== false) {
    die("Something went wrong");
}
```

The update.json looks like this:

```json
{
    "from_version": "1.2.3",
    "to_version": "1.2.4",
    "requirements": {
        "php": ">=5.6.0"
    },
    "files_updated": {
        "views/anotherfile.php": "hashofanotherfiletxt",
    },
    "files_added": {
        "web/somefile.txt": "hashofsomefiletxt",
    },
    "files_updated_md5": {
        "views/anotherfile.php": "md5hashofanotherfiletxt",
    },
    "files_added_md5": {
        "web/somefile.txt": "md5hashofsomefiletxt",
    },
    "files_deleted": [
        "controller/filetobedeleted.php"
    ]
}
```

The hashes of the updated and added files are used to check the integrity of the new versions of the given files included in the ZIP file. Normally, the hashes and ``files_added`` and ``files_updated`` are used to check the integrity (using ``base64_encode(sodium_crypto_generichash($fileContent))``). However, for installations that lack native support of libsodium (PHP <= 7.2), the md5 hashes are used instead, as the polyfill is too computation intensive and tends to lead into timeouts, and the ``signature``from the update API is ignored.

## Performing the file-update

First, a check is run if every file to be updated or deleted exists, and none of the files to be added already exists. Checks are performed if all files are writable and enough space is available on the device. Additionally, the version given in ``from_file``is checked against the current version. If any check fails, the update is aborted and a list of problems is displayed.

Then, a directory ``runtime/backups/1.2.3`` (with 1.2.3 being the old / current version) is created. Every file listed in the update.json (file_updated and file_deleted) is backupped into this folder (sub-folders are recursively created).

Finally, the actual operations are performed.

## Performing database migrations

At any time, the update script shows a list of database migrations that are not applied yet. If there are any, a button is presented, that performs the update. This relies on calling Yii's mechanisms for doing so, so for this functionality, there is a dependency on the Yii scripts.
