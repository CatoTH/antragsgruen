# Version history

## Version 4.16.0 [not released yet]

- An optional mechanism for background job execution is introduced, making it possible to send e-mails asynchronously (therefore not blocking regular requests).
- The deadline circle at the top of the homepage now also shows dates if more than one future deadline exists. It can also be deactivated entirely.
- The export functions for motions in the admin list now allow to combine one or multiple motion types, with all motion types being selected by default.

### Version 4.15.3 (2025-05.13)

- For votings, it is now possible to set if the e-mail-address, the name or the organization of the voters is to be displayed (if at all).
- On speaking list, it is now possible to not show the list of applicants in the publicly visible speaking list view.
- Bugfix: When copying a motion to a different consultation, the tags and the version were not transferred.
- Bugfix: Sending e-mails via SMTP only worked with SMTP Servers requiring authentication. (thanks to tiran133)
- Bugfix: Amending motions with list items was broken if amendments were to only affect one particular place.

### Version 4.15.2 (2025-03-29)

- When cloning a consultation, the agenda is now also copied by default.
- Bugfix: Editing and withdrawing amendments was not available as menu items on small screened devices.
- Bugfix: When cloning consultations with user groups that have administrative permissions to only a subset of motions, these restrictions were not copied to the new consultation.
- Bugfix: The "My Motions" sections could show not accessible new versions of submitted motions.
- Bugfix: If motions/amendments submitted by organizations received additional support by users (not necessary for submission), an irrelevant notification about minimum support could be sent.
- Bugfix: Multiple line breaks without starting a new paragraph in a motion could lead to empty deletions in the amendment view.
- Bugfix: Amendments were often broken when a new section was added to the motion type after a motion of this type has already been created.

### Version 4.15.1 (2025-02-08)

- Next to the status-dropdown for motions and amendments, there is now a link to a reference page, explaining the uses of these different statuses.
- Bugfix: Uploading files from the home page did not work properly.
- Bugfix: If a voting was set to be shown in a motion view, it was not shown on the dedicated voting page anymore.
- Bugfix: Sorting document groups on the separate document page did not work properly.
- Bugfix: Some tabs opened in parallel could interfere with logging in using a second factor.
- Bugfix: When logging in using a second factor, one would always end up in the site home page, not the page where one started the login.
- Bugfix: Some empty paragraphs could lead to problems with creating amendments if only one paragraph could be changed.
- Bugfix: The activity log did not show some actions related to supporting/liking motions or amendments.

## Version 4.15.0 (2024-11-30)

- Several improvements for user account administration:
  - User accounts can now be protected using Two-Factor-Authentication through TOTP. For individual users or all users of dedicated installations, this can be enforced.
  - Admins can now delete user accounts.
  - Admins can now prevent specific user accounts from changing their password, e.g. if it is supposed to be a shared user account where admins manage the password.
  - Admins can now enforce users to change their password the first / next time they log in.
  - Admins can now disable the functions for users without access to a consultation to request access.
  - Users that didn't receive the initial account confirmation e-mail can now request another confirmation e-mail after an hour.
- When going to the Terms and Conditions and/or the Privacy page and back, you will end up in the same consultation than before now.
- Custom input fields in motion forms can now have explanations. They can also be set to be "encouraged but not required" - that is, the user can skip them but will get a warning if they do so.
- Content pages can now have attached files, just like the welcome text on the home page.
- Access to content pages can now be restricted to logged in users, admins or specific user groups.
- When merging amendments into a motion, the default setting now is to create a resolution, not a new motion.
- Security improvement: When logging in, and a new verion of PHP (like 8.4) suggests a stronger default password hashing, the stored hash is updated accordingly.
- A new translation is provided: Montenegrin (thanks to Danilo Boskovic)
- Administrators of an installation can modify the behavior of the CAPTCHAs on registration (see README).
- Some compatibility issues with PHP 8.4 were resolved.
- Bugfix: Tabular data was not encoded correctly in the PHP-based PDF export.
- Bugfix: The setting to open (PDF-)files in new browser tabs was not considered at several places.
- Bugfix: The PDF with all amendments embedded into the motion text could not be generated if a Weasyprint-based PDF layout was selected.
- Bugfix: If a motion with a proposed modified version was copied, merging that proposed version was not possible anymore.
- Bugfix: It was possible for users to submit amendments for withdrawn motions.
- Bugfix: When using "previous / next motion" links for pagination, motions and resolutions were not properly separated.
- Bugfix: Some Youtube-Videos could not be embedded.
- Bugfix: When copying a consultation with motion types whose permissions were restricted to a specific user group, these permissions where not properly copied.
- Bugfix: If an uploaded logo was deleted, the shown logo was broken instead of falling back to the default logo.

### Version 4.14.2 (2024-09-08)

- Security advisory x41-2024-002:
  - Illegitimate content could be stored in the motion reason. (CVE-2024-46884. Credit: X41 D-Sec GmbH, Eric Sesterhenn)
  - Redirects to external pages could be injected. (CVE-2024-46882. Credit: X41 D-Sec GmbH, Eric Sesterhenn)
  - E-Mail verification after signup could be bypassed. (CVE-2024-46883. Credit: X41 D-Sec GmbH, Yassine El Baaj)
  - E-Mail verification after e-mail change could be bypassed. (CVE-2024-46883. Credit: X41 D-Sec GmbH, Yassine El Baaj, JM)
- Bugfix: The PDF-export of all amendments was not working.
- Bugfix: The PDF-export of amendments with proposed procedure was not working when using Weasyprint.

### Version 4.14.1 (2024-08-25)

- If a filter is set in the motion list, this will also filter the motions to be exported in the export row above (PDFs, ODTs, ODS etc.).
- Setting a modified proposed procedure is now more streamlined, as changing the proposed status to "Accepted (modified)" now directly brings one to entering the modified text, and saving it will lead back to the motion / amendment.
- Bugfix: The PDF export with included proposed procedures was sometimes broken.
- Bugfix: Publishing proposed procedures from the admin list only worked for amendments, not for motions.
- Bugfix: When multiple versions of a motion exist, the ODT / PDF export list showed all versions, instead of only the newest one.
- Bugfix: If a motion replaces one of a different consultation, editing as an admin removed the connection between these two motion versions.
- Bugfix: Merging amendments into a motion was broken if the motion's proposed status had a proposed change but then changed to another proposed status.

## Version 4.14.0 (2024-05-20)

- A new default motion type template exists, "progress report". It includes a resolution and a progress section. The latter can be edited inline from the document view by administrative users. An editorial group of user can be defined that has permissions to edit these progress report section without having any other addition administrative privileges.
- Super-admins can now change the e-mail-address / logins of registered users, not only their passwords.
- If the home page layout "Tags" is used, it is now possible to show only the list of tags, and the actual motions on tag-specific sub-pages. This is mostly aimed towards consultations with hundreds of motions.
- Optionally, "previous motion" and "next motion" links can be activated on motions, to enable browsing through the motions without having to go back to the home page.
- The admin motion list can now be filtered by motion type.
- For list votings where a number of options are presented to vote on and delegates can choose which one to vote for, there is now also an option to allow an explicit "General Abstention" to explicitly vote for none of the given options.
- Exporting "Inactive" motions and amendments from the admin motion list now also includes unpublished items.
- Users can be assigned a voting weights, for example if they represent multiple delegates. If so, their vote counts as multiple votes.
- For creating motions and/or amendments, the two options to create it as single delegate or organization can new each be restricted to specific user groups.
- The custom theme editor now also allows to (un)set the boldess, upper-casing and text shadows of headings.
- For newly created application motion types, the signature is now optional and the gender field is not automatically generated anymore.
- It is now possible to set no agenda item for motions.
- Redis support for caching is now bundled in the default Antragsgrün distribution, so no need to manually install packages anymore. The setup can be done in the config.json.
- LaTeX will be deprecated for rendering PDFs. Instead, a new rendering based on Weasyprint is introduced, that should handle several edge cases better and will make it easier to customize PDF layouts. The default PHP-based PDF renderer will remain unchanged.
- The internal caching system has been optimized, preventing parallel processes generating the same cache, which might overload systems with a high number of users after cache invalidation.
- For very large consultations (1.000 motions/amendments or more), setting the viewCacheFilePath option in config.json now optimizes several aspects and is an officially recommended setting.
- Bugfix: Some texts were not properly escaped, allowing XSS by consultation admins.
- Bugfix: The "Allow more supporters than required" could not be deactivated for support collection phases before publication.
- Bugfix: Several issues with the predefined organisation list for user administration were fixed.
- Bugfix: CAPTCHAs were sometimes hardly readable.
- Bugfix: In the support collecting page, for amendments, the required supporters of motions were shown, not of amendments.

### Version 4.13.2 (2024-02-18)

- Bugfix: the version check in the editor that lead to warnings is now disabled.
- Bugfix: The list of previous speakers was not expandable.
- Bugfix: In rare cases, the sorting of motions on the home page was not working properly.
- Bugfix: The new shortcut to create amendments for one paragraph section directly from the motion had problems when two lists came right next to each other.
- New translations are provided: Dutch (thanks to m-rtijn and MickVolt) and Catalan (thanks to gtriasg and reixacu).

### Version 4.13.1 (2023-12-09)

- As admin, is now possible to edit the list of internal (proposed procedure) tags, just like the public ones.
- Proposed procedure tags can now be assigned directly in the procedure overview page.
- As admin, it is possible to deactivate private notes on the site.
- If amendments are set up to be restricted to one paragraph, then each paragraph in the motion now shows a direct link to the amendment creation page, with that paragraph pre-selected.
- It can be set up so that external links and PDF-links will be opened in new, blank browser tabs/windows.
- Security: Consultation admins could grant privileges to other consultations within the same site.
- The backlink on the "my account" page leads to the consultation where the user was coming from.
- Admins can now download Excel/XLSX-exports of amendments and the comments to motions.
- Motions and amendment in the "My motions" section of the home page are now sorted by prefix.
- Bugfix: When resolutions were shown on a separate page, title prefixes / motion signatures were shown.
- Bugfix: Motion history might also show changes of invisible motions.
- Bugfix: Comparing motion versions did not show changes in titles.
- Bugfix: If a motion section was removed from a motion type that already had motions, then amendments to this motion could not be merged individually anymore.
- Bugfix: If a voting block was deleted with motions/amendments assigned to it, then these motions/amendments could not be assigned to new voting blocks anymore.
- Bugfix: If a motion had an empty optional section, amendments adding text to that section were breaking the motion view.
- Bugfix: Copying a motion with amendments amending another amendment to another consultation was breaking the assignments between the amendments.
- Bugfix: The first line of an amendment was determined incorrectly if the first change was in the second or later amendable motion section.

## Version 4.13.0 (2023-10-29)

- WARNING: if you are using a PHP 7.4 or older, then update to PHP 8.0 or newer first before installing this update!
- For larger consultations using the Speaking Lists, there is now a separate live server component, allowing real-time updates of the speaking lists at reduced load on the server. As it runs on Java, it is not compatible with "traditional" webhosting. Sites running on antragsgruen.de / discuss.green are getting the real-time update feature automatically.
- It is now possible to copy/duplicate a motion within a consultation.
- When editing a motion or amendment as admin, it is possible to set the status to "Obsoleted by another amendment" or "Obsoleted by another motion" and specify which one it is in a dropdown.
- When creating a new consultation based on an existing one, it is now possible to choose if motion types, tags and/or user permissions are to be copied from the existing consultation or not.
- When a list of organisations is specified in the consultation settings, this list is shown as a drop-down when creating / inviting new users.
- The list of resolutions made during a consultation, which is by default listed above the motion list on the home page, can now be put onto a separate page - or replace the motion list, moving the motion list to a separate page.
- The list of tags / topics can now be re-ordered and existing tags can be renamed.
- When choosing "Tags / categories" as home page layout, then the consolidated category-list at the top can now be deactivated.
- It can now be set up so that the modified version proposed as part of a proposed procedure is shown inline as part of the motion.
- Besides of exporting all motions as a ZIP-file containing single ODT files, a single ODT file containing all motion texts can now be exported.
- When a new version of a motion is created during merging amendments (in contrast to a resolution being created), the status of the new motion version can be explicitly specified.
- The ODT export now also supports numbered lists.
- The motion list can now be filtered for To Do items (that is, motions/amendments that need to be screened) and also shows the To Do action for items on that list as part of the Status.
- The motion list now persists its filter and sort settings for each user session, until changed or reset.
- If an amendment is set to show the full text by default, this now also affects the PDF export.
- Merging a single amendment into a motion now also handles amendments only changing the title of the motion.
- The maintenance mode page is now specific to a consultation; that is, delegates bookmarking a link to a consultation that is still in maintenance mode can open that bookmark later and get to theat very consultation, not the generic home page.
- On single-site instances, user registration can be disabled altogether by setting the allowRegistration key in config.json to false.
- Bugfix: If a draft of a revised motion (by merging amendments) existed and the motion list was opened, the original motion was not shown anymore by default.
- Bugfix: Some edge cases around uploaded logos breaking the PDF export or not being shown on the page were resolved.
- Bugfix: Super-admins could lock themselves out of protected consultations.
- Bugfix: Putting a active speaker back into the speaking waiting list did not work - the speaker vanished from the list completely.
- Bugfix: Closing the full screen mode of a speaking list was leading to an error page.
- Bugfix: If not-logged-in users are allowed to support motions/amendments, they showed up as empty bullet points in the supporter list. Now they have to enter their name.
- Bugfix: The delete button in the admin motion list was shown even if no delete permissions were granted and it was therefore non-functional.
- Bugfix: If a motion collecting supporters was edited by an admin, then no publication mail was sent later when it was actually published.
- Bugfix: The login screen shows the correct consultation in the breadcrumb links.

## Version 4.12.0 (2023-05-29)

- User groups are now more powerful administrational tools:
  - User groups can now receive admin rights for specific administrational tasks.
  - These tasks can be restricted to a subset of motions, like motions of a specific type, agenda item or tag.
  - User groups can be allowed to only see and read incoming unpublished motions, without any editing rights.
  - User groups can now be renamed.
- Super admins (registered in the config.json) can now perform more user administration using the UI, like setting the name, organization and new passwords for registered users.
- The number of votes users can cast on a voting session can be limited. It is thus possible to present a list of motions or candidates and have the users choose up to that specified number of them.
- For the proposed procedure, it is now possible to set the status "Accepted (Modified)" for motions too and to specify a modified version of it to accept. Previously, this was only possible for amendments.
- A new motion versioning system is implemented, replacing just using the signatures for versioning. Instead of "M1new2", the signature will now remain the same but the version is saved separately.
- In the admin motion list, replaced motions are now hidden by default if the newer version is also shown. Tn additional filter gives the option to show all versions of a motion in the list.
- Motion sections can now be set up to hold Right-to-Left text, like Farsi, Hebrew or Arabic.
- The date format can be set independently from the language. Also the date format yyyy-mm-dd is supported (besides dd/mm/yyyy, mm/dd/yyyy, dd.mm.yyyy).
- It is possible for admins to create tags for a consultation but disallow proposers of motions to specify these tags themselves.
- Admins can add additional proposers of a motion or amendment.
- When merging amendments into motions or in the motion admin view, admins can write a protocol that can be public or not.
- In tabular data sections of motions / applications, as an admin it is now possible to present a SELECT box with pre-defined options for the proposer to choose from.
- Personal comments written by users to motions / amendments are now indicated on the home page to the respective user.
- It is possible to set up PDFs and exports of motions / amendments so that the proposed procedure is included. This is set up on a per motion type level.
- If a motion or amendment has more than 50 supports, then only the most recent few are shown by default, with the option to explicitly show all.
- Internal / Plugins: Additional language variants are now handled as part of the plugin system, not by placing files into messages/ anymore. The latter will stop working with version 4.13.
- Bugfix: The diff and line splitting did not work properly with grapheme consisting of multiple code points.
- Bugfix: Changing the amendment text as admin does not clear the motion's view cache in all cases.
- Bugfix: PHP-based PDF rendering lead to overlapping lines when the text contained nested lines without line numbering.
- Bugfix: Setting the time of agenda items was not possible in locales using AM/PM.
- Bugfix: Uploaded logos and background images could not always be shown with enabled maintenance mode.
- Bugfix: When using "Tags / categories" as the home page layout, the motions were not sorted by prefix.
- Bugfix: It was impossible to create motions for motion types that had no title section defined.

### Version 4.11.1 (2022-12-10)

- Bugfix: The fresh installation mode was broken with MariaDB.
- Speaking lists can now have more than only two sub-queues, e.g. for women, men and diverse speaking lists.
- Some layout issues regarding votings and speaking lists on small screens and full screen mode were resolved.
- Initiator-based amendment merging is not in the wizard anymore, as this is hardly ever used, let alone useful.
- Some minor compatibility issues with PHP 8.1 and 8.2 were resolved.

## Version 4.11.0 (2022-11-28)

- WARNING: if you are using a PHP 7.3 or older, then update to PHP 8.0 or newer first before installing this update!
- A separate document page can be enabled, allowing to upload multiple files in folders. The documents are visible to all users and can be downloaded individually or all in one as a ZIP.
- For individual motion types, "amendments based on amendments" can be enabled. They allow users to propose alternative versions of an amendment. This is mainly targetet to statute amendments.
- Single amendments can now be set to show the full motion text including changes by default, instead of only showing the changed parts of the motion.
- Speaking lists were improved in a few ways:
  - The speaking list administration is now directly linked from the admin page.
  - It is now possible to remove and reorder speakers.
  - Points of order are supported, always appearing at the top of the speaking list. Optionally they can be enabled even when the list is closed.
  - Admins can deactivate the possibility for users to change the name when applying for a speaking list (also making applying a click quicker).
- The voting functionality was improved in several ways:
  - Votings can now be reordered.
  - When closing a voting, there is now an additional option to close it without publishing the results right away.
  - A separate voting page can be enabled, showing up in the menu.
  - Votings can have a timer, indicating a countdown to vote. (It is not binding though, it still needs to be closed by hand)
  - Votings can be chosen at the creation wizard of a site or consultation.
  - Newest votings will be shown at the top of the voting list.
- The full screen projector mode can now also show custom content pages.
- The full screen projector can be set into a split screen mode, showing two motions / amendments / custom pages next to each other.
- It is now logged when users are added to or removed from a user group. A log is visible for admins.
- It is now possible for an admin to show the last edit date of a motion or amendment on the home page.
- Reading comments can now be restricted to the same user group as writing comments.
- The comment sections of individual motions or amendments can now be closed without affecting others.
- Bugfix: setting the maximum number of printed initiators on PDFs was broken.
- Bugfix: Base statute texts were shown in the "New motions" section in the sidebar.
- Bugfix: When a consultation was restricted for users and the consultation was not set as the default one, applications to get permission were mis-directed to the default consultation.
- Bugfix: If an encrypted application PDF cannot be embedded into a "all-in-one" PDF, the error message is now shown on a separate page instead of overlapping the previous application.
- The plugin system was enhanced to support integrating Single-Sign-On systems, specifically SAML-based ones.
- The plugin for the Antragsgrün Site used by the german greens now supports restricting permissions to members of specific regional divisions of the party.
- Vue.JS was upgraded from version 2 to 3

### Version 4.10.1 (2022-07-01)

- Public votings assigned to user groups now also show the users that have not voted yet in the admin view.
- The voting result page now has a full screen mode and does not reload automatically anymore.
- Bugfix: Some constellations of whitespaces and line breaks at the end of list points could lead to problems with amendments.
- Bugfix: When saving binary files to the file system was enabled, copying / moving motions to other consultations broke uploaded images and PDFs.
- Bugfix: If users started to create a motion or amendment but did not confirm them, the draft was visible on the home page, but not accessible.

## Version 4.10.0 (2022-05-21)

- The user permission system was now replaced by user groups.
  - The access to a site is now configured on the standard "consultation settings" page.
  - The previous site access page is now exclusively used to configure users and user groups.
  - There are a few pre-configured user groups with special meanings (Site administrator, consultation administrator, proposed procedure editor, participant).
  - Arbitrary additional groups can be added.
  - Creating motions, amendments, comments and supporting them can be restricted to one or several of these groups.
- The voting functionality was improved in several ways:
  - Votings can now have simple questions that are not attached to any motion or amendment.
  - Votings have different answer options: besides "Yes/No/Abstention", it is now also possible to have simple "Yes/No"- and "Presence"-votings (Roll calls).
  - It is possible to restrict voting to specific user groups.
  - Voting results can be exported into a spreadsheet.
  - Users responding to a "Presence" (Roll call) vote can be assigned to a customer user group, to be eligible for further votings, creating motions/amendments etc.
  - If voting is restricted to one or more user groups, then a quorum can be set that needs to be reached for the voting to be valid.
- Antragsgrün now comes with a plugin to integrate the user administration with OpenSlides:
  - It allows to log in into Antragsgrün using the username/password of an OpenSlides page.
  - Using a separate proxy app, it allows to automatically synchronize users and user groups from OpenSlides to Antragsgrün.
  - It needs to be set up by a system administrator per site.
- The speaking list has a few improvements:
  - The speaking list now has a separate site for users in the menu.
  - Speaking lists supports setting a timer per speaker.
  - One speaking list per agenda item can be created.
- Amendments can now be further restricted to only affect one particular location within one paragraph.
- There are new motion statuses "Quorum reached" and "Quorum missed".
- The CAPTCHA system is reset for a user after a successful login. This solves issues when multiple users are behind the same IP address.
- The full-screen projector now also shows the initiator and status of a motion / amendment.
- The menu at the top of the page was reordered into a more logical order.
- A new e-mail-sending library (Symfony mailer) is used. Amazon SES can now be configured as mailer, too (by editing the config.json directly).
- Bugfix: The button to apply for a speaking list was shown, even if applying was not possible.
- Bugfix: More than 26 numbered list points were not supported for latin character based numbering

### Version 4.9.1 (2022-02-12)

- Bugfix: A modified amendment text as proposed procedure only got shown in the internal list after saving it a second time.
- Bugfix: If both an agenda and a proposed procedure is used, some motions could appear twice on the proposed procedure.
- Bugfix: If an image section was accidentally set as amendable and an amendment was created without changing the image, the PDF could not be rendered.
- Bugfix: Votings could break after a participant has deleted their account.
- Bugfix: If creating statute amendments was restricted to logged in users, the create link did not work properly.
- Compatibility with PHP 8.1

## Version 4.9.0 (2021-12-12)

- Online voting functionality was added. Admins can now define voting blocks, where users can vote on amendments and motions to be adopted or rejected. A documentation about this feature is located at https://sandbox.motion.tools/help#votings .
- Submitted amendments can now optionally have tags, too, if set up in the motion types.
- There now is a full-screen view of motions and amendments, to show them on projectors on live events. It can be activated on the title of the motion/amendment.
- Parts of motions' and in particular applications' motion types can be marked as "non-public", meaning that this information will be only visible for admins and the proposer itself.
- The accessibility was improved in some parts, especially drop-down selections, while reducing the page load time.
- A new amendment numbering scheme was introduced more appropriate for english environments: "M1 A1" (Motion number + Amendment number)
- It is now possible to copy a motion including all its amendments to a different consultation or agenda item without marking the original one as moved.
- To prevent brute force login attempts, a entering a CAPTCHA is now required after three failed login or account recovery attempts. Standalone hosted versions of Antragsgrün can optionally require it for every single login attempt, by adding the loginCaptcha flag to config.json.
- Statute amendments can now be created for an agenda item if the according motion type was set.
- Bugfix: If an amendment was assigned to an agenda item, the agenda item could not be deleted before the amendment was un-assigned first.
- Bugfix: Tabular data, like in applications, were not exported into spreadsheets.
- Bugfix: an empty "Supporting" section was shown in motions and amendments, if only the "liking" function was enabled.
- Bugfix: Some bugs in edge cases with LaTeX-based PDF rendering were solved.
- Bugfix: if fixed font width was specified but no line numbers, then line number placeholders where shown in the text.
- Support for Internet Explorer was dropped.
- PHP-Support: PHP 8.1 is not yet supported, please use PHP 8.0 until support will be added in the next minor version. This is the last version to support PHP 7.2.

### Version 4.8.1 (2021-08-28)

- Bugfix: The consultation page did was not shown when a statute amendment was withdrawn.
- Bugfix: After saving a proposed procedure of an amendment or motion, the selected voting block was not shown.
- Bugfix: If an amendment was assigned to an agenda item explicitly, it still showed up for a second time at the motion in the agenda.
- Bugfix: The REST API didn't work correctly with statute amendments
- Bugfix: To prevent motions that cannot be saved in the backend due to special characters in the motion slug, all slugs are now strictly transliterated to latin characters.

## Version 4.8.0 (2021-06-27)

- Statute amendments are now explicitly supported. They have the following characteristics:
  - Admins can create the base statutes that can be amended. This base text will not be visible regularly.
  - Statute amendments are displayed and created like normal motions. That is, they will be shown like normal motions on the home page and receive a regular prefix like "S1". Their content is using the diff view of amendments, though.
- If PDFs are uploaded as an image in applications, they will be converted to a PNG. Until now, this did not work at all. This only works if ImageMagick is set up on the server.
- When selecting an image for uploading in applications, only supported file types are selectable now.
- Admins can now explicitly assign amendments (including statute amendments) to agenda items, also to agenda items different from their base motion. In this case, they appear like regular motions on the home page.
- Admins can now see an activity log for each motion and amendment, chronologically listing all relevant events for it (supports, comments, proposed procedure changes etc.).
- Admins can now assign motions and amendments to user accounts, e.g. when the motion was created by an admin or an anonymous user before creating an account.
- For amendments, instead of only the condensed change view, it is now also possible to show the changes in the context of the whole motion text, by clicking on the settings icon next to each section headline.
- Motions can now be downloaded as a PDF with all screened amendments embedded inline into the motion text.
- When merging amendments into a motion:
  - Text entered by the admins can now optionally receive a blue color, to distinguish admin-entered text from the base motion or changes made by the amendments.
  - When merging multiple amendments affecting the same passage of a motion, the merging algorithm now tries more aggressively to merge them into the text, relying on the editing person to resolve the conflicts. Previously, it just refused to merge the second amendment and repeated the colliding amendment below the paragraph.
  - Indications about line numbers of the original motion are now shown at the side.
  - Text could be striked through, but that formatting was not saved. It now is.
- As long as the maintenance mode is activated, admins now get an alert on the page about it being active, including a link to the page where they can deactivate it.
- If admins create a motion or amendment in behalf of a user, no confirmation mails about the submission is sent to the user anymore (if confirmation mails are activated in the first place).
- When official supports are collected for a motion or amendments, it is now optionally possible to support them "non-publically". That is, only logged in users can see the names of those supports.
- In the proposed procedure, admins can set internal tags to motions and amendments in order to filter them more efficiently later on in the motion list.
- Export to OpenSlides is now an advanced feature than can be activated in the motion list under "Functionality".
- When notifying a user about a proposed procedure of her motion or amendment, and an editor was set as responsible in the backend and has a Reply-To-E-Mail-address set, then this address will be taken, instead of the address of the editor actually triggering the notification.
- Improvements for consultations with more than a thousand motions / amendments:
  - An internal consultation setting "adminListFilerByMotion" can now be set in the database to separate the admin list into one list per motion.
  - The caching of motion views was improved so that it does not need to be recalculated as often anymore
  - An optional file-based view cache was introduced, configurable by setting "viewCacheFilePath" in the config.json. Its purpose is not to overload Redis with binary data.
- For motion types, it is now possible to deactivate entering a name as proposer altogether, by selecting "No proposer" in the "From"-dropdown of the motion type settings.
- Bugfix: for PDF-only applications, the collective PDF merging all applications could not be generated.
- Bugfix: Uploaded GIFs could not be rendered into application PDFs if LaTeX-based PDF-rendering is used.
- Bugfix: the "reset to original motion text" button when editing an amendment text as admin did not work.
- Bugfix: a rare bug when sending e-mails through sendmail was fixed that could lead to broken links in the mail.
- Bugfix: when editing an amendment that changed a headline, the change to the headline was not marked as edited text

### Version 4.7.1 (2021-04-18)

- Bugfix: If a woman quota was set up in the support collection phase, the notification e-mail to the initiator about the minimum number of supporters did not take into account this quota.
- Bugfix: A motion creation bug was fixed that happened if an organization list drop-down was set up and the "Grünes Netz"-login was used.
- Bugfix: When using LaTeX-based PDF-rendering for applications, the tabular data to the right part of the page looked strange when having multiple lines
- Bugfix: If an amendment changed big parts of an intermediate headline in a motion text, the change was not correctly indicated using the red and green text colors.
- Bugfix: Selecting multiple topics / tags for motions did not work.
- Bugfix: When allowing multiple topics / tags, removing all tags through the admin backend did not work.
- Bugfix: The REST API did not work if the consultation path had a dash in it, followed by a number.
- Bugfix: A (broken) link to a resolution PDF was shown on the home page, even if no PDF was activated for this motion type.
- Bugfix: The supporting section of motions / amendments was partially shown to admins even if it was activated for nobody.

## Version 4.7.0 (2021-02-13)

- WARNING: if you are using a PHP 7.1 or older, then update to PHP 7.3 or newer first before installing this update!
- Speaking lists for live events can now be administered.
   Participants of the event can put themselves on and remove themselves from the speaking list, and the admin can choose and indicate who to speak next on a separate administration page.
  - This is implemented as a "live" feature, which means, no reloading is necessary for either the admin or the users.
  - Speaking lists can either be linear, or use a quota system, e.g. to enable alternating between women and other/open speaking lists.
  - Users can see the current speaker and the waiting list both on the home page (more detailed) and on motion/amendment pages (as a more subtle footer).
  - Admins can add people to the waiting list themselves, and reorder the waiting lists using drag&drop
  - Admins can choose if a qualified login is necessary for users to add themselves on the speaking lists or not.
  - The speaking lists can also be used as a stand-alone-feature, without the need of having motions, amendments or an agenda.
- The wizard for creating new sites and consultations now also offers to create PDF/Text-based applications and speaking lists.
- A new motion section type is implemented: Video Embeds. Using it, users can add links to Videos, for example to support their candidature. If it's a video hosted on Vimeo, Youtube or Facebook, it will be embedded into the application, otherwise a link is shown.
- The pink deadline circle on the consultation is now also shown if multiple motion types with the same deadline exist.
- First, small beginnings of a REST API are implemented, currently with read-only access. It can be enabled in the site component settings. The documentation can be found at [docs/openapi.yaml](docs/openapi.yaml)
- Tabular data sections now skip empty fields when displaying them.
- Some texts can now be changed per motion type, to change the wording depending on context. For example, in the "Create a motion" form, instead of the default "Motion or amendment?" text, there can be different explanations for different motion or application types.
- Applications / Candidatures are now only shown in two-column mode until the first embedded PDF or Video appears. It then switches into single-column-layout to make the PDF/video better readable/watchable.
- The URL slug of a motion can now be changed on the admin page.
- Applications (or other motion types with images) now receive a `og:image` tag for better image detection when sharing the applications.
- Bugfix: If e-mail-notifcations about published motions for the initiating users are set up, but the submission form does not explicitly ask for an e-mail-address, no e-mail was sent. Now, it is sent to the e-mail-address of the user account.
- Bugfix: If a user has previously put her e-mail-address on the e-mail-blocklist, then saving the account settings lead to an error message.
- Bugfix: if the link to a proposed procedure was forwarded, only the proposed status and the accept button was shown, not the modified text of an amendment.
- Internal: the login system now supports plugins for retrieving user accounts from external sources, e.g. CMS systems with an existing user database. As an example, a integration into Drupal/CiviCRM can be found in the plugins/drupal_civicrm-folder.
- Internal: Plugins can now provide custom amendment numberings and add extra settings and data fields for amendments and motions.

### Version 4.6.3 (2020-11-29)

- Compatibility with PHP 8 / Composer 2.
- Bugfix: Merging amendments into a motion failed if previously an amendment for that motion was deleted that had a modified version.
- Bugfix: When deleting an amendment after adding it to a proposed procedure's voting block, it remained visible within the proposed procedure.
- Resuming a previously saved draft when merging amendments into an motion could fail if in the meantime an amendment has been hadded.

### Version 4.6.2 (2020-09-26)

- Motion types that do not have a text part, for example PDF-uploaded applications or financial reports, can now also have comments, if the permission is set accordingly in the motion type settings.
- SVG images can now be uploaded as logo.
- Some browser warnings regarding cookie settings are resolved.
- TLS-encryption can be set for sending e-mails through SMTP
- Bugfix: LaTeX-based PDF rendering sometime failed or gave wrong line numbering in nested lists.
- Motions embedded into an agenda are now shown, even if they are replaced by a resolution above.
- Bugfix: if a resolution was replaced by a new version of that resolution, but that new version was deleted, the original resolution was still not shown on the home page anymore.
- Bugfix: The box shadow could not be deactivated when customising the layout.
- Updates to core libraries.

### Version 4.6.1 (2020-07-11)

- Several minor accessibility and performance improvements.
- If supporting a motion or amendment is allowed after publication, then the deadline for supporting it is now not affected anymore by the deadline for the submission.
- Updates to core libraries (Yii and jQuery).
- Bugfix: A bug that lead to unchanged text before changed text being shown as deleted and inserted again was fixed.
- Bugfix: When creating a new amendment based on an existing one as admin while the settings "Amendments may only change one paragraph" was set, the pre-defined changes taken over from the existing amendment were not properly editable.
- Bugfix: motions of the status "Draft (admin)" could be accessed using a known direct link.
- Bugfix: wrong time transformation when using the english version.
- Bugfix: When showing the diff view of amendments changing the text within list items, some words could get lost.
- Bugfix: If amendment codes didn't have numbers, this could break the screening process.
- Bugfix: Resetting the custom color theme to default values took the color values from before the accessibility-related design changes.

## Version 4.6.0 (2020-04-12)

- Numbered lists are now supported. This includes different list item styles (like (1), 1., a., A.), manually setting the list item number for each list item (e.g. to skip numbers) and also using non-standard list numbers like "15b" (e.g. for laws where paragraphs have been inserted afterwards). This enables discussions and amendments for statutes and laws.
- Several improvements were made to increase the accessibility of Antragsgrün:
  - Better navigation with screenreader
  - Easier navigation using the keyboard
  - Better contrasts
  - Generally, Antragsgrün aims to comply with the WCAG 2.0 AA rules
- It is now easier to edit the agenda of a consultation, as the changes are immediately saved and it is not necessary anymore to explicitly use the Save-Button at the bottom of the agenda.
- If a new amendment is screened / published while a new version of the motion is being created (by merging other amendments into it), this new amendment will automatically appear in the merging view. A small hint will appear, notifying about this addition, and with a link to the first paragraph affected by this amendment. The new amendment is inactive, by default, and needs to be actively activated for merging into the affected paragraph. Vice-versa, if an amendment is unpublished while merging, the amendment hint vanishes automatically. Already applied changes remain, though.
- Users can now support motions and amendments in all phases, if this is set up.
- There is now an option to include the name of an amendment submitter to the bookmarks shown to the right of the motion text. As this has the danger of looking awkward once the names are getting too long, this is not activated by default.
- If an amendment affects only one specific line of a motion paragraph, one line before and after the affected one are now also shown in the amendment (confirmation) view, to give more context when reading it.
- If a motion or amendment is submitted as an organisation, the confirmation page now also shows the contact person.
- Regarding proposed procedures:
  - If proposed procedures are used, it is now possible for the admin of a consultation to set a default Reply-To e-mail-address for each motion committee member.
  - The e-mail about a proposed procedure to the initiator of a motion now contains a link that can be forwarded to other people. These users can see the proposed procedure, too, and agree to them.
  - Bugfix: when a motion was replaced by another one, the assignment was not saved properly.
- If a motion is moved to another agenda item or consultation and a reference stays at the old place, then there now also is a reference from the new place to the original place to indicate where this motion originally came from.
- There is a new function to embed a translation button to motion, amendment and consultation pages. To enable it, go to Settings -> Appearance page and activate the Translation component. You can choose either Google or Bing. This will show a button at the top right of a motion/amendment that allows visitors of this site to translate this page using the specified service.
- Technical change: Internally, some first components start to use the Vue.JS library.

### Version 4.5.1 (2020-03-23)

- Bugfix: When editing an amendment as admin, the date picker was missing for the resolution date of the initiator organisation.
- Bugfix: When changing a motion text, the PDF was not regenerated in some cases.
- Bugfix: When using PHP-based PDF-rendering and alternative PDFs for motions, the heading of this section ("Alternative PDF") was appended to the previous page.
- Bugfix: When setting up alternative PDFs optionally, and then not uploading one for a motion, then the regular PDF was not generated for this motion.
- Bugfix: Writing paragraph-based comments were broken when the motion view was statically cached.

## Version 4.5.0 (2020-02-26)

- The paragraph-based editing mode for merging amendments into motion, introduced in version 4.3.0, was visually improved to closer resemble the regular motion view, with the amendment toggles to the right of each paragraph. This improves the legibility of the motion text.
- The appearance settings of a site are now moved to a separate page.
- There is a new consultation home page layout, targeted towards idea-collecting set-ups. The most recent comments are shown at the top, with a short abstract of the comment, to stress the discussion aspect. Below, the list of motions / ideas is shown, with a list of tags / topics, handlers to sort the motions by different criteria and filter them by tags / topics. The new layout can be set up on the "Appearance"-page.
- The agenda of a consultation can now have date separators, and the individual agenda items can have explicit times.
- Images can now have a maximum size for the PDF version. Signatures in applications, for example, now have a maximum size of 5x3cm.
- If an image or a PDF in a motion or application is optional, it is now possible to delete an already uploaded one without uploading a replacement.
- For PDF-based applications, the mostly empty cover page in front of the generated PDF is removed; the generated PDF is now exactly identical to the uploaded one.
- The change mentioned above also allows attaching a pre-layouted PDF-version of a motion, replacing the automatically generated PDF.
- If final resolutions are shown on the home page, preliminary resolutions replaced by final ones are now not shown anymore.
- If there is a supporting phase for submitting new motions or amendments, it is now possible to publish a page listing all motions and amendments currently looking for supporters. This page is disabled by default and can be activated in the Appearcance settings. It will be linked to at the sidebar of the consultation home page.
- Notification e-mails now use a very simple responsive e-mail template.
- Mailgun and Mandrill are not supported anymore, for now.
- The social sharing buttons in the motion and amendment views were removed, as they were practically never used and visually unpleasing.
- It is now possible to deactivate the breadcrumb links in the appearance settings.
- Agenda items, including their motions, can be hidden from the proposed procedure.
- When creating a resolution using the "merge amendments"-functionality, there is now a "remove text"-checkbox above the motion reason to exclude the reason from the final resolution more easily.
- If there is a global password set for a consultation and it is still possible to log in as a user (e.g. as admin), the e-mail-based login form is now a bit more hidden, as the focus will be on the global password for most regular users.
- Bugfix: if a motion section was empty (e.g. a motion that did not have a reason), that section could not be edited when merging amendments.
- Bugfix: too long motion titles are now breaking the layout and some functionality less than before.
- When entering a organisation name when creating a motion/amendment, using brackets at the end of the name was not possible.

## Version 4.4.0 (2019-12-26)

- WARNING: if you are using a PHP version older than 7.1, then update to PHP 7.2 or newer first before installing this update! Version 4.4 is the first version of Antragsgrün compatible with PHP 7.4, with a new minimum requirement of PHP 7.1.
- Admins can now publish a list of arbitrary files to the home page of each consultation, e.g. PDFs with information about this event. The upload functionality can be found when editing the "Welcome"-message.
- Motions can be moved to another agenda item, while retaining a reference to the new location in the old place.
- Motions (including all amendments, comments and so on) can be moved to another consultation in the same site, optionally while retaining a reference to the new location in the old place.
- Admins can now assign "responsibilities" to motions and amendments, i.e., other administrational users that are in charge of it. This functionality is not enabled by default and can be enabled either through the motion list ("Functions" at the top) or the motion type settings.
- Amendments can now have initiator and supporter settings independent from the base motions.
- Admins can now set a predefined list of organisations that initiators can choose from when creating a motion or amendment, instead of entering the organisation freely.
- When it is necessary to collect supporters for a motion/amendment before submitting it, and the gender field is activated, it is now possible to not only set a minimum number of supporters, but also a minimum number of women supporting it.
- A slightly new homepage layout variant is introduced, which first shows the agenda, then the motions, but by default hides the amendments below the motions, with the option to toggle the list. This is meant for consultation with hundreds of amendments where the sheer amount of amendments makes the list way too long.
- Sub-agenda-items on the home page are now shown with the full agenda item code, i.e. "2.3." if the parent agenda item has the code "2." and the sub-agenda-item "3.".
- When merging amendments into motions, conflicting text passages can now be marked as "handled" and therefore hidden.
- Custom themes can now modify the font size of the headings and the width of the page container and sidebar.
- If an uploaded PDF is part of a motion (e.g. an application PDF or a financial report) and the PDF-version of the overall motion is generated, the embedded PDF is now also embedded for the LaTeX-based mechanism. To enable this, LuaLaTeX is used instead of XeLaTeX.
- Bugfix: When merging amendments with a modified version as proposed procedure, the paragraph's collision section was initially showing the collisions of the original amendment, not the proposed version.
- Bugfix: If a motion assigned to a tag was deleted, that tag could not be removed from the consultation anymore.

### Version 4.3.1 (2019-10-27)

- When using the admin backend, admins can now always edit an amendment so that multiple paragraphs are amended, even if under normal circumstances only one paragraph may be changed per amendment.
- Bugfix: Numbered lists were not split correctly into single items when merging amendments into the motions; the numbering was reset to 1 for each single item.
- Bugfix: It was not possible to write private notes to motion paragraphs.
- Bugfix: Withdrawn amendments were under some circumstances shown in the bookmark list to the right side of the motion text.

## Version 4.3.0 (2019-10-21)

- Hint: 4.3 will be the last version compatible with PHP versions below 7.1.
- The merging of amendments into motion has greatly been improved. Instead of generating one big, monolithic text with all changes and conflicts, the motion is now split into different paragraphs. For each paragraph, the amendments to be merged into the motion can be selected separately and on-the-fly while editing the new version of the text.
- Please note that previous merging drafts that were not completed, are not accessible anymore after upgrading to this release due to incompatibilities with this new merging system.
- The inline amendment mode in the regular motion view (using the green bookmarks at the right) can now be used on devices with small screens as well.
- Several styling improvements, especially for devices with small screens (e.g. smartphones).
- The "My motions" and "My amendments" sections on the consultation overview now list all motions/amendments that one has created, ininiated or expressed (dis)like towards. Not only the ones ininiated as before. This makes it easier to check on motions/amendments that are for some reason not visible anymore on this site (withdrawn, merged, ...)
- The "Using Antragsgrün"-hint in the sidebar can now be disabled through the admin interface.
- Several performance improvements for large installations with hundreds of amendments, especially in the motion view.
- It is now possible to set a detailed voting result for motions and amendments, either from the backend when editing the motion/amendment, or when merging amendments into a motion when confirming the new motion or resolution.
- When creating a custom color theme, the values of the previously selected default theme will be taken as default values. Previously, always the default values of the green/magenta-theme were taken. When resetting the color values, you can now explicitly choose between the defaults.
- RSS-Feeds have now a info page with some explanations about what RSS-Feeds actually are. The direct links in the sidebar are therefore replaced by a link to this info page.
- In the proposed procedure, a second notification to the initiator of an amendment can be sent by mail, e.g. if the proposed procedure has been heavily modified.
- The ODT-version of a motion / amendment now also shows the list of supporters.
- To avoid exceeding use of paper when printing out motions with many supporters, it is now possible to set a upper limit for the number of printed supporters.
- Bugfix: When installed in subdirectories, using custom color themes lead to broken icons.
- Bugfix: The proposed procedure overview for regular users was not accessible on devices with small screens.
- Bugfix: Long-broken references to OpenID-based login were removed.
- Bugfix: Deleting tags form a consultation after a motion was assigned to this tag was not possible.
- Bugfix: In a few cases, the internal proposed modified amendment was linked instead of the main amendment. The page was not accessible.
- Bugfix: In some cases, broken links were shown in the Activity log

### Version 4.2.3 (2019-07-18)

- Bugfix: If a user subscribed to notifications and had is account deleted afterwards, the site crashed later on when this notification was attempted to be sent.
- When a consultation is protected with a password, it can now be selected right away if only this one consultation or all should be protected.
- Some internal libraries have been updated.

### Version 4.2.2 (2019-05-20)

- Consultations can be protected with a password. This password can be spread amongst eligible users. This serves as a quick&dirty alternative to dealing with user accounts.
- If a single amendment is merged into the motion and there exists a modified adoption to it, one can now choose between those two versions. (Previously, only the original amendment was merged, the modified version was not offered)
- The activity log was slightly improved: comments are teasered and the pagination doesn't grow endlessly.
- If multiple motion sections of the type "Title" were defined, the second, third etc. ones will now be treated as regular sections and therefore be displayed.
- If a consultation is configured as having only one motion and that motion is overhauled using the "merge amendments"-method, the new version will now be set as the new default motion for this consultation.

### Version 4.2.1 (2019-03-23)

- Bugfix: logos whose filename had special characters were not displayed in the generated PDF.
- Bugfix: When uploading a PDF for an application as not-logged-in user, the PDF was not displayed correctly in the confirmation view.
- Bugfix: Tags containing an ampersand (&) were double-encoded. This bugfix only affects newly created tags.
- Bugfix: If comments were deleted by the user, this resulted in rather awkward entries in the activity log.
- Bugfix: The styling of the links in the sidebar when editing motions or amendments was inconsistent.
- The list of admin email addresses for notifications in the consultation settings can now be separated both by commas and semicolons.
- Multi-Site mode: it is now possible to activate plugins on a per-subdomain basis.
- Multi-Site mode: If no Reply-To is set in a consultation, the Admin's email address will be set as Reply-To for outgoing e-mails.

## Version 4.2.0 (2019-02-23)

- It is now possible to modify the layout of Antragsgrün, e.g. by choosing custom colors or uploading a background image. To create a custom theme, go to "Settings" -> "This consultation" -> "Custom theme".
- When logged in, users can now add private notes to each motion, amendment and motion paragraph.
- If a site is set to be accessible only for a closed list of users, there is now an option for users to apply for this list. The admin will receive an e-mail in this case and will be able to accept or reject this request.
- Administrators can now specify if only natural persons, only organizations or both can submit new motions and amendments. Default is both.
- Improvements regarding uploaded images:
  - Images uploaded at one consultation can now be used in other consultations of the same site as well.
  - Distorted images in content pages are now prevented.
  - Instead of only uploading new consultation logos, the logo can now also be chosen from the already uploaded images.
  - Bugfix: The logo of a consultation is now also shown on the login page, if the consultation is only accessible for logged-in users.
  - Bugfix: The uploaded logo of a consultation was not shown on some generated PDFs where it was actually intended to.
- Some internal libraries (Yii, Bootstrap) were updated.
- Bugfix: if an admin of a consultation was deleted without revoking the admin permissions first, the permission page could not be rendered anymore.
- Bugfix: In the PDF-collection containing all motions, the header page of a motion contained the status header of the previous motion, if the PHP-based PDF-renderer was used.
- Bugfix: Exporting the PDF-collection as ZIP file did not work for the PHP-based PDF-renderer.
- Bugfix: Merging amendments produced an error in some cases with many amendments.
- Bugfix: Amendments to motions with numbered lists (OLs) behaved incorrectly, especially when merging them into the motion.

### Version 4.1.1 (2018-12-09)

- In the list of consultations, the newest consultation now appears at the top.
- Clicking on changes in the public version of a merging draft now always opens a tooltip with the summary of the amendment, including the proposers of that amendment.
- The custom status string is now also shown in the admin motion list.
- When administering the list of users eligible to access a consultation, it is now possible to completely remove a user again.
- System administrators can now delete user accounts from the site-wide user list.
- Bugfix: the date of the last saved draft when merging motions was not set correctly on Safari.

## Version 4.1.0 (2018-11-17)

- Several improvements regarding applications, especially the generated PDFs:
  - Motion types can now force motion titles to have a certain beginning, like "Application: ".
  - A new PDF template is introduced specifically for applications, if the LaTeX-based PDF-renderer is used.
  - For each section of a motion type, it is now possible to specify if the title will be explicitly printed in the PDF of not.
  - If the uploaded image is way too big (bigger than 1000x2000px), it is resized to keep the size of the PDF at a reasonable size.
- Two new statuses are introduced: "Resolution" and "Resolution (preliminary)". Motions of these states...
  - are shown on the consultation home page separately in a slightly different view (initiators are not mentioned anymore).
  - have a different header as regular motions in the web- and the PDF-view.
  - can neither have amendments nor comments.
- Several improvements regarding merging amendments into motions / creating the final motion version:
  - When editing the merged view with the amendments' change inlined into the text, this draft can be exported into a PDF to document the merging process.
  - After creating the final text, administrators can decide if the new version of this motion is a regular motion again, or a (preliminary) resolution.
  - Bugfix: If an amendment had certain statuses, it was selectable for merging, but was not actually merged then.
- Several changes regarding the proposed procedures:
  - Proposed procedures are now an optional functionality per motion type. They are disabled by default.
  - When exporting the proposed procedure list into ODS, there is now an option to also include the comments or to only include public visible proposed procedures.
  - When sending a notification to users regarding a proposed procedure, the content of the mail can now be modified by the person sending the notification.
  - If an amendment is obsoleted by another amendment with a proposed modification, this modification is also shown in the context of the (proposed) obsoleted amendment.
  - Admins can set a proposal as being accepted by the user. (This is getting logged)
  - Bugfix: It was not possible to delete admin comments in the proposed procedure.
  - Buffix: Prevent a bug when creating a proposed procedure that collides with another amendment.
- When creating a motion, the confirmation page now shows a preview of the generated PDF.
- In the initiator form, the resolution date for organizations submitting a motion is now optional. An additional optional field to add one's gender was added.
- The admin interface for adding/removing supporters of a motion/amendment now has a function to copy the full list of supporters to the clipboard in a format suitable to paste it into the full-text field later on (to easily transfer the supporter list from one motion to a new one).
- The diff view in amendments now usually show the whole affected line, instead of cutting off the line after the last changed word, giving some more context to the change.
- Add an option to enforce a confirmation checkbox on registration.
- The performance of the admin motion list was improved for large installations (> 1000 amendments), by reducing the number of database queries.
- Bugfix: When a motion type was created from the scratch and motion sections positioned right were added, the layout didn't switch to two-column mode.
- Bugfix: When an optional image was not uploaded, the LaTeX-based PDF export did not work.
- Bugfix: Prevent broken sites when too long consultation titles are entered.
- Bugfix: Under some circumstances, a motion assigned to an agenda item did not appear to be assigned correctly in the consultation home page.
- Internal: translatable strings can now include a comment / description which will be displayed in the translation page.

### Version 4.0.4 (2018-10-16)

- Bugfix: Creating the proposed procedure list could fail.
- Bugfix: A ODS-list could not be exported if a motion had no initiators.
- Bugfix: Another bug regarding nested list items and line numbers in PDF generation was fixed.
- If no proposed procedure is set, the amendment merging page now preselects all amendments for merging (except for global alternatives).

### Version 4.0.3 (2018-10-14)

- Bugfix: in nested lists in the PHP-based PDF renderer, the line numbers were not aligned correctly to the lines.
- Bugfix: added extra safeguards to prevent stale popovers when merging amendments into motions.

### Version 4.0.2 (2018-09-15)

- Bugfix: The PDF export of applications failed if a tabular data section was added in the motion, but no data rows were set.
- Bugfix: Underlined text was not rendered as such in the PHP-based PDF renderer.
- The WYSIWYG-editor CKEDITOR was updated, including some bug fixes.
- Temporary files for PDF and ODS generation are not stored in /tmp/ anymore, as there are hosters that block access to this directory. Instead, runtime/tmp is used.
- In the installation, host names including ports (like localhost:3306) are now supported for the database connection.

### Version 4.0.1 (2018-09-02)

- Bugfix: Motion comment tables were not created correctly when MySQL tables names are case-sensitive.

## Version 4.0.0 (2018-09-01)

- Antragsgrün now includes an update mechanism to install new versions from the web interface.
- The comment system was improved:
  - It is now possible to reply to comments. The replies will be displayed indented below the main comment.
  - The layout of comments is more compact and less visually cluttered.
  - Now it is easier and more flexible to get e-mail-notifications about comments to motions/amendments: when writing comments, it is possible to opt-in to e-mail-notifications about new comments, either for comments to the same motion, for direct replies, and for all comments to the same consultation.
- The system for editorial texts (like the legal pages or the welcome message) has been overhauled. New features include:
  - It is now possible to upload images using drag&drop when editing editorial texts.
  - It is now possible to create new editorial content pages, optionally linked to from the main menu.
  - It is now possible to add a login message, that appears above the login screen.
- Instead of only setting the deadline for motions and amendments, there is now an alternative complex phase system with time ranges for motions, amendments, comments and merging amendments into motions, including a debugging tool for admins.
- The RSS-feeds are now embedded in the header of the consultation page, making them auto-discoverable.
- If the "amendment merging" mode of a motion is used just to create a new version of the motion, and no amendments actually exist, the process is now greatly simplified.
- There now can be more than only one "pink create-motion button" in the sidebar. This can be configured on a per-motion-type basis.
- There are first beginnings of a plugin system based on Yii2's module concept. It's still subject to major changes, though.
- On the user account page, there now is a tool to export all user data in JSON-format.
- It is easier to create PDF-based application motion types, as there now is a template for that when creating a new motion type.
- Selecting the site layout and PDF layout is now done using thumbnails of the layouts, not by their rather cryptic names.
- The PDF-introduction of a motion can now be set on a per-motion-type basis.
- The sender name and reply-to of sent emails can now be set on a per-consultation-base (it was on a per-site-base before).
- The logo of a consultation can (and has to be) uploaded now in the consultation settings. This enables using it as PDF-logo, and prevents mixed secure content warnings.
- It is now possible to specify the visibility of the page in search engines / set the noindex-tag. By default, only the home page is indexable. Alternatively, everything or nothing can be set to indexable.
- In the regular motion view, it is now possible to go to a specific line by simply typing in the line number and pressing enter.
- Add a way to include the commercial FPDI-PDF-plugin.
- It is now possible to restrict the access to one consultation to specific users, while leaving other consultations open for all.
- In installations that use SAML/Grünes Netz for authentication, it is now possible to restrict the login to a specified list of users.
- Improve compatibility with reverse proxies.
- The default consultation path is not "std12345678" (random number) anymore, bust simply "std".
- When creating a motion and it is not clear what agenda item it should belong to, the agenda item can now be set from the motion creation form.
- Image and PDF upload fields in motions / applications now show the maximum file size.
- When creating a motion, optional fields are now marked as such.
- Bugfix: Improved compatibility with servers that do not support URL rewriting ("pretty" URLs).
- Bugfix: When creating a new version of a motion and changing the motion type of this new motion afterwards, the changes between the two versions could not be displayed.
- Bugfix: When Latex was not activated, new consultations were created with no PDF after saving the motion type the first time.
- Bugfix: The list of tags to select when creating a motion is now sorted in the same way as in the consultation setting page.
- Bugfix: When a section was completely empty in a motion and a amendment was created inserting something to this section, the original motion could not be displayed anymore.
- Bugfix: When a motion type's create setting was set to "nobody", editing already created motions as an admin was impossible.
- Bugfix: When creating a new version of a motion, it was not possible to merge none of the amendments.
- Bugfix: After changing the line length, the old line was still in the cache for LaTeX-based PDF rendering.
- Bugfix: When submitting an application containing a photo and the admin enabled submission confirmations by e-mail, the confirmation e-mail did not correctly link to the image.
- Obsolete: The old, non-fuctional OpenID-based Wurzelwerk-login was removed.
- Obsolete: The facebook image feature was removed, as nobody used it and was rather tricky to use anyway.

### Version 3.8.3 (2018-05-12)

- Bugfix: At ByLDK PDF Layout, the default introduction was shown all the times at amendments.
- Bugfix: The ZIP-file of Antragsgrün could not be extracted using Windows.

### Version 3.8.2 (2018-03-27)

- Displaying the proposed status didn't work when the production package of Antragsgrün was used.
- Compatibility with PHP versions <= 7.1 was broken.
- Title changes in amendments are now shown when merging the amendments into the motion.

### Version 3.8.1 (2018-03-18)

- The publication date of a motion can now be edited in the administration.
- "Accept/Reject all changes" is only shown when merging amendments, if there are actually amendments to merge.
- When creating an amendment, "Global alternative" is not shown anymore, if amendments are restricted to one paragraph, as it didn't work anyway.
- Bugfix: The publication date and slug was not set when a motion was created by merging amendments.
- Bugfix: When editing a motion in the backend without changing the submission/resolution date, the seconds in the timestamp were unnecessarily reset to zero.
- Bugfix: Removing other admins from a consultation was not possible with some browsers.
- Internal: Vendor prefixes are now added by default, slightly increasing the size of the CSS, but therefore adding some extra browser compatibility.

## Version 3.8.0 (2018-03-10)

- Proposed procedures for motions and amendments
  - Administrators of a consultation can now manage the proposed procedure of a motion / amendment. This includes setting a proposed status, a modified version of the amendment text.
  - The proposer of the motion / amendment can be notified about this proposal and optionally agree to this proposal.
  - Motions and amendments can be grouped into voting blocks to indicate which ones are mutually exclusive in case of a voting.
  - The proposed procedure and the voting blocks are optionally published on a separate page.
- After replacing a motion by a new version, e.g. by merging amendments, there is now a view comparing both versions of the motions. That way, it is much easier to see what has actually changed.
- Amendments to the title of a motion are now shown like changes to the text, using bookmarks at the right side of the motion.
- Support for OpenSlides 1 is removed, only 2.1+ is supported.
- This public draft of a in-progress amendment merging progress can now be displayed in full-screen mode.
- Antragsgrün is now compatible with PHP 7.2
- Mailjet is now supported as E-Mail Service
- It's now possible to link to published motions and amendments by their prefix, using URL-schemes like /*consultation*/*motionPrefix*, /*consultation*/*motionPrefix*/*amendmentPrefix* or (possibly ambiguous) /*consultation*/*amendmentPrefix*
- Updates to several core libraries
- Bugfix: A bug in the line numbering after manual line breaks was fixed.
- Bugfix: A multi-page PDF that was uploaded as part of a application and then exported as PDF again, collided with the tabular data of the application.
- Bugfix: When it's allowed to select multiple tags when creating a motion, the tag selection required to select all tags at once.
- Bugfix: The "Create a motion"-Button in the agenda did not work if the user was not logged in and creating a motion required being logged in.
- Bugfix: When downloading the PDF-collection of motions / amendments, the file extension was sometimes lost.
- Bugfix: When merging amendments into a motion that already had "NEU" / "NEW" in the prefix, the program could crash due to inconsistent handling of upper-/lowercase
- Bugfix: The Open Document generation is now a bit more tolerant towards unsupported HTML tags
- Bugfix: When an admin created a user account in the backend, sending e-mails to that user did not work.
- Internal: Refactoring of the layout hooks, allowing more site-specific custom codes.

## Version 3.7.5 (2017-11-15)

- Security advisory X41-2017-011 / CVE-2017-16824/16825/16826: Several XSS attacks have been fixed.
- Bugfix: The autosuggest in the admin list did not work properly
Thanks to Eric Sesterhenn of [X41 D-SEC GmbH](https://www.x41-dsec.de/) for reporting this issue.

## Version 3.7.4 (2017-11-12)

- Bugfix: It was not possible to set the parent motion in the admin backend
- Bugfix: After finishing the merging of the amendments, the public draft is now deleted
- After a motion has been overhauled by merging the amendments, the amendments are now still shown as bookmarks in the view of the original motion
- The DBJR-PDF-Layout was slightly improved

## Version 3.7.3 (2017-11-04)

- Bugfix: Creating a new amendment based on an existing one did not work when amendments are restricted to only one paragraph
- Bugfix: Sorting of amendments on the consultation page is fixed
- The appearance of amendments and withdrawn and modified motions/amendments was slightly improved on the consultation page
- The name of the initiator of an amendment is added to the e-mail-notifications about its publication
- On the public version of the amendment merging draft, the code of an amendment is shown after the change. Furthermore, it is now possible to open the amendment-diff-popup and the normal amendment page by clicking on a single change  
- The header of the amendment merging page (editing mode and public version) is now sticky 

## Version 3.7.2 (2017-10-21)

- Bugfix: Motions / applications with tabular data could not be saved from within the admin backend
- Bugfix: An empty agenda item code lead to a crash in the home page of a consultation
- Bugfix: It was possible to set a broken urlPath of a consultation, so parts of the site would not work anymore

## Version 3.7.1 (2017-09-30)

- The ODS-Export of amendments now include a column with the status of the amendments
- Bugfix: Several Bugs in the LaTeX-based PDF-export have been fixed:
  - Nested enumerated lists were not rendered correctly
  - Headings (H1-H6) in combination with line numbers lead to extra line numbers after the heading
- Bugfix: ByLDK-PDF-Template did not respect the introduction text specified in the consultation settings
- Bugfix: When using TCPDF-based PDF-rendering, BR-tags lead to double newlines
- Bugfix: ODS-Export of Amendments could not be read by some versions of LibreOffice

## Version 3.7.0 (2017-09-03)

- Initiators of motions can now merge amendments into their motions, if this is allowed by the consultation settings. This function can be restricted to cases where no amendment rewriting is necessary.
- Proposers of motions now can receive an e-mail if a amendment to their motion is published (enabled by default)
- When merging all amendments into the motion at once...
  - drafts are saved periodically and can be set as public. This way, regular users have read-only access to the current work-in-progress draft of the revised motion.
  - when a paragraph of an amendment led to a collision with changes made by another amendment, all changes of that paragraph of that amendment were marked as colliding. Now, we include as many changes as possible into the merged version and only leave the actually colliding changes in the colliding paragraph below the merged version. This reduces the amount of necessary manual work.
  - it is now possible to exclude some amendments from merging, to prevent lots of collisions for amendments that replace major parts of the motion
- Replacing several consecutive paragraphes in an amendment is now displayed in a more sensible way (first all deletions in a row, then all insertions; they were alternating before)
- Administrators can now be set per consultation, without granting them access to all consultations of this site
- It's possible to change the motion type of a motion now after creating it. However, this works only between motion types that are structurally similar.
- Amendments can now be marked as "global alternatives", replacing the whole content of a motion. Their contents will not be displayed using an inline diff, as this does not make sense in this case. When merging a global alternative into a motion, the motion will be replaced completely, and no amendments will be moved to the new version of the motion.
- If a motion or amendment is created using another one as template by an admin, the text is cloned as well, not only the initiators.
- Several improvements to the inline editing of the agenda
- A more detailed manual about the functionality of Antragsgrün is provided
- The timestamp of a motion or amendment now shows the time when it has been officially submitted, not when the first draft has been created. The latter is still shown before the submission.
- Strike-Through formatting is not allowed anymore in motion sections that are amendable, as this messed up the amendment function.
- Improvements to the Installer
- We don't pretend anymore you could use Antragsgrün with IE <=9
- Updates to several core libraries
- Redis, Excel and SAML Authentication is not part of the default installation anymore, reducing the size of the package. It can still be installed as an optional dependency, though.

This release was mainly sponsored by the [German Federal Youth Council](http://www.dbjr.de/).

### Version 3.6.10 [Not released]

- Bugfix: When downloading PDFs and ODTs of amendments with Firefox, umlauts were not encoded correctly in the file name

### Version 3.6.10 (2017-06-17)

- Bugfix: Merging a single amendment into a motion could crash the system if a new paragraph was inserted.
- Bugfix: After rewriting an amendment while merging another amendment into the motion, it could not be edited anymore, as the text has been empty 

### Version 3.6.9 (2017-06-05)

- Feature: A French translation is provided, thanks to the work of Antoine Tifine of Les Jeunes Écologistes
- Bugfix: In some very rare cases, a bold formatting in the PDF-Export was not finished correctly, leading to the rest of the document appearing as bold.
- Bugfix: Merging amendments did not work with PHP 5.5

### Version 3.6.8 (2017-05-09)

- The deadline of motion / amendments now also affects supporting the motion / amendment in the support collecting phase
- Bugfix: Nested agenda items were not always sorted correctly 
- Bugfix: The amendment PDF-collection did not export all amendments if multiple motion types were present
- Bugfix: In the consultation sidebar, not all motion PDF-collections had an own link if multiple motion types were present
- Improvements in the packaging, deleting some unnecessary and symbolic links leading to warnings when extracting the files on Windows or uploading them using FTP

### Version 3.6.7 (2017-04-30)

- The installation wizard is now internationalized, using english as default language
- Bugfix: the site configuration was not accessible

### Version 3.6.6 (2017-04-24)

- Bugfix: Fix a race condition in the editor when creating amendments in Single-Paragraph-Mode (fixes #227)
- Bugfix: When editing a whole block of text when creating an amendment, this could lead to faulty amendment indicating changes at places that were not changed at all.
- Bugfix: When cloning a motion type, the possible ways of liking/supporting a motion were not copied to the new motion type
- Bugfix: Motions/Amendments in the state "Draft (Admin)" should be readable if you know the link, as it comes after "Collection Supporters"
- Bugfix: never display "line 0", fall back to "line 1" instead.

### Version 3.6.5 (2017-04-17)

- Bugfix: Displaying the affected lines of an amendment sometimes began one, sometimes even two lines too early (and therefore the line numbering).
- Bugfix: Better error handling when uploading images or PDFs
- Bugfix: Wrong label when confirming an account

### Version 3.6.4 (2017-04-12)

- Compatibility: The installation should now work with disabled POSIX-extension
- Internationalization: Some strings in the login/account-creation-process were translated to english

### Version 3.6.3 (2017-04-10)

- Bugfix: The installation was broken
- Bugfix: Remove "consultation/index" from the URL in single-site-installations 

### Version 3.6.2 (2017-04-10)

- Bugfix: Creating a motion/amendment using another one as template works now in combination with the support colling phase
- Bugfix: The main page of the manager (introduction to Antragsgrün) was reachable on subdomains / from within a consultation
- Bugfix: Withdrawing an amendments that is not yet visible, the motion is not deleted anymore, but gets a special withdrawn status (was implemented in 3.6.1, but not correctly)
- Bugfix: ODT-Export of amendments did not work when the base motion did not have a prefix
- Bugfix: a broken placeholder in a motion-supporting-INPUT

### Version 3.6.1 (2017-03-19)

- When withdrawing a motion that is not yet visible, the motion is not deleted anymore, but gets a special withdrawn status
- An optional new internal status "Submitted (screened, not yet published)" was introduced between "unscreened" and "screened". 
- Bugfix: When editing a motion submitted by an organization, the organization name was blank in the admin interface
- Bugfix: The entered HTML code was not cleaned when editing a motion from the admin interface

## Version 3.6.0 (2017-02-17)

- Admins can now edit the motion text in the backend without breaking existing amendments. If there are conflicts between the changes made and the amendments, they have to be resolved manually.
- Admins can merge an amendment into the base motion. This creates a new version of the motion. The original version of the motion and the amendment are kept for reference. If the changes of the amendment are conflicting with changes proposed by other amendments, the conflicts need to be resolved manually.
- Single motions can now be set as non-amendable by the admin
- Export to OpenSlides 2 is now supported (OpenSlides v2.1 or later required)
- Local translations variants files can be created without committing them to the repository
- Improvements to the support collection phase
- Editorial changes in amendments can be deactivated
- If a motion or amendment is withdrawn, a notification is sent to the admin
- A widget to show the content of an amendment in a popover is introduced, e.g. in the admin motion list
- A combined PDF of a motion and all attached amendments is available in the admin-motion-list
- In PDF-collections with page numbers, the page numbering is done on a per-motion/amendment-basis now, not for the whole collection
- Support for Redis as cache & session store
- Improvements to the OpenDocument-Export
- Internal: Port JavaScript modules to TypeScript
- Improvements to the integration of Grünes Netz of the German Green Party: organization keys can be resolved and the transmitted name / organization are fixed

This release was mainly sponsored by the [German Green Party](https://www.gruene.de/).

### Version 3.5.1 (2016-11-30)

- Introduce a way to delete consultations and sites
- Introduce a sandbox-mode, where you can play around with Antragsgrün on a temporary site that will be deleted after 3 days.
- Some methods to prevent automated bots from posting spam to comment forms

## Version 3.5.0 (2016-11-12)

- Support for H2, H3 and H4 headlines in motion texts
- Support adding local stylesheets/themes using configuration files (and added some documentation on how to to so) 
- Improvements in the Plain-PHP PDF-Export, like attaching user-uploaded PDFs
- PHP-Based PDF templates are now available on LaTeX-enabled systems, too
- Supporting motions and amendments is now supported (if "All" is selected in the motion type settings)
- Bugfixes in the ODT-Export and line numbering
- Introduction of layout hooks (this enables more flexible layout variants)
- Improved caching, especially on the home page and when generating PDFs
- A new theme inspired by the German Green Party's CI
- Several bugfixes in the Diff-algorithms
- URLs in the motion text are not automatically converted to links anymore
- A new motion status "Draft (Admin)" is introduced
- Uploaded images are optimized (stripping metadata) if Imagemagick is set up. This also prevents some complications with LaTaX-based PDF-generation.

### Version 3.4.3 (2016-08-22)

- A pre-bundled package of Antragsgrün is now provided for easier installation, especially on hosts with no shell access
- Updates to some internal libraries

### Version 3.4.2 (2016-08-18)

- Bugfix: displaying uploaded PDF-files wasn't working properly
- Motions in the agenda view of a consultation are now sorted by their prefix

### Version 3.4.1 (2016-08-01)

- Bugfix: bundle bootstrap-datetimepicker in a modified version, compatible to jQuery 3
- Bugfix: building the bundled JavaScript-files was broken
- Upgrade to jQuery 3.1.0 and Bootstrap 3.3.7

## Version 3.4.0 (2016-07-17)

- A new wizard to create new consultation is introduced. It's used when creating new consultations within one site, when creating new sites in a multi-site-environment and when installing a new single-site-instance of Antragsgrün.
- Single-Motion-Consultations are now supported: consultations that only exist of one single motion that is, skipping the regular home page.
- Internationalized subdomains (IDNA) are now supported (containing characters like german Umlauts)
- Motions are not amendable anymore before official publication (e.g. in support collection phase and during screening)
- The login system was slightly improved: the login / logout action is now valid for all subdomains at once
- Bugfix: a dependency necessary for direct SMTP-support for system e-mails was missing

### Version 3.3.4 (2016-06-11)

- Bugfix: empty lines vanished under some circumstances
- Bugfix: a class name collision in EmailNotifications.php was resolved
- Bugfix: uploaded images in motions / applications were not shown 

### Version 3.3.3 (2016-05-29)

- Style fixes (some labeles were invisible)
- Revoking likes/dislikes was not possible

### Version 3.3.2 (2016-05-16)

- Once a motion or amendment is submitted after collecting enough supporters, no more supporters can be added or revoked.

### Version 3.3.1 (2016-05-07)

- Remove the "motion/"- and "amendment/"-parts from the URLs
- Replace Shariff by a custom sharing widget, saving HTTP Requests

## Version 3.3 (2016-05-06)

- There is now a activity page for every consultation, listing all bigger events of this consultation in a timeline
- The administration has been split up into three separate parts: the Motion List, the To Do List and the Settings Page
- Exporting motions and amendments can now be done from the motion list. There now is an option to include or exclude withdrawn motions/amendments
- The social share buttons were moved from the main content to the sidebar
- Use SimpleSAML instead of OpenID to authenticate against Wurzelwerk / Grünes Netz. OpenID is still supported as legacy system
- A (very basic) user list to the admin page
- Bugfix: Inserted / Deleted paragraphs were not formatted as such in the OpenDocument-Export

### Version 3.2.3 (2016-04-15)

- Using the enter key when creating an amendment does not create a new paragraph anymore, but only a line break. This is a "hack" to improve the diff.
- Editorial hints in amendments are no shown in the admin backend (and can be edited)
- Bugfix: In some cases, single lines were shown as modified with deletions where no deletion was actually made.
- Bugfix: When amendments are numbered by the first affected line number, the numbering got wrong once more than two amendments started at the same line.

### Version 3.2.2 (2016-04-13)

- Bugfix: some supporters where tagged with "You!" for logged-out users
- Bugfix: better string normalization
- Bugfix: editing amendments on the administration page did not work when single-paragraph-mode was activated

### Version 3.2.1 (2016-04-06)

- Support for Mailgun as mail service
- Bugfix: Admins could not edit drafts they created for other persons

## Version 3.2 (2016-04-03)

- We now support a "support collection phase", where a motion has to be supported by a given number of supporters before it is officially published.
- Motions now have a more verbose URL including a secure token. The URL of unpublished motions is therefore not guessable anymore.
- Support database table prefixes
- Support policy of motions and amendments can now be set independently
- Compiled assets (stylesheets, scripts) are now included in the repository. This makes installation from the repository a lot easier.
- To-Do-Items on the admin page are now sorted (by date)
- The supporters of a motion/amendment can now be reorderd in the backend using drag&drop
- The "Help" link in the menu is only shown after explicitly created by the admin (on the admin page) 
- Bugfix: creating a new consultation based on a template did not clone the motion sections and therefore created empty motion types
- Bugfix: Dangling &nbsp;'s at the end of a line were not stripped at a motion, but at the amendments.
- Bugfix: Motions with section-based comments could not be edited in the admin-screen
- Bugfix: a security problem was fixed that occurred under the following two conditions: 1) Everyone can create a motion, without login and 2) Supporting motions was enabled
- Bugfix: when global line numbering was enabled, unscreened motions could not be viewed by the admin
- Bugfix: the amendment creation view showed the creation policy for motions, not for amendments
- Bugfix: the "tag"-based home page was buggy in several ways 
- Bugfix: Texts containing links to domains with german umlauts could not be saved

## Version 3.1 (2016-02-14)

- PHP 7.0 Compatibility
- Rewrite of the Diff-Algorithms
- New Inline Amendment Merging
- Upgrade of Bootstrap, CKEditor and FuelUX
- Gender-Star
- Don't allow strike-through text in amendments
- More informative filenames for PDF- and ODT-exports
- When submitting motions or amendments, the text is sent as part of the confirmation e-mail
- Fixing a security problem while using the web-based installer
- Support for image-upload in motions / applications, esp. for PDF-export of images
- Add a two-column PDF template designed for applications
- In the motion/amendment-list for admins, amendments are shown right next to their motions
- All ODTs of motions and amendments can be downloaded as a bundled ZIP-file

This release was mainly sponsored by the [German Federal Youth Council](http://www.dbjr.de/)

## Version 3.0 (2015-11-16)

Complete rewrite of Antragsgrün. Some features included:
- Introduction of motion types
- Introduction of the agenda
- Usage of HTML instead of BBCode for encoding motions / amendments
- Basic structure for internationalization
- Technical: Migrating from Yii1 to Yii2, Upgrading to Bootstrap 3, using a test-driven approach based on Codeception

This rewrite was mainly sponsored by the [German Green Party](https://www.gruene.de/)

## Version 2.0 (2013)

Version 2 of Antragsgrün introduced the concept of multiple sites for one installation and multiple consultations inside one site 

The changelog for version 2 can be found at [https://github.com/CatoTH/antragsgruen/blob/v2/History.md](https://github.com/CatoTH/antragsgruen/blob/v2/History.md)

## Version 1.0 (2012)

