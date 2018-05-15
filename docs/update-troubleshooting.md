## Update Troubleshooting

### My user account does not have administrative privileges

Site administrators with access to the update tool are not administered using the web tool, but using the ``config.json`` configuration file. If you want to add a new user account to the list of site administrators, look for the user ID in the ``users``-table and add this ID to the ``adminUserIds``-Array in ``config.json``.

### update.php asks me for a key

This key is a security measurement to ensure that only the site administrator can access the update script. You can find it in the ``config/config.json``file, in the line with the key ``updateKey``. Enter the value between the brackets into the field.

If you enter the correct value and the form keeps appearing, it is likely an issue with the browser cookies. Please perform the following steps:

- Check that cookies are enabled.
- Delete all cookies coming from the site's domain, specifically any cookie called ``update_key``.

### Can I manually disable the update mode

The update mode is controlled by the presence of the ``updateKey`` in the ``config/config.json``. Just remove this line and the update mode will be disabled again.
