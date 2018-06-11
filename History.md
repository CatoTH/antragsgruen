# Version history

## Version 4.0.0 [not released yet]

- Antragsgrün now includes an update mechanism to install a new version from the web interface.
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
- Improve compatibility with reverse proxies.

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
- Bugfix: When editing a motion in the backend without changing the submission/resulution date, the seconds in the timestamp were unnecessarily reset to zero.
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
- Bugfix: When an admin created an user account in the backend, sending e-mails to that user did not work.
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
  - when a paragraph of an amendment lead to a collission with changes made by another amendment, all changes of that paragraph of that amendment were marked as colliding. Now, we include as many changes as possible into the merged version and only leave the actually colliding changes in the colliding paragraph below the merged version. This reduces the amount of necessary manual work.
  - it is now possible to exclude some amendments from merging, to prevent lots of collissions for amendments that replace major parts of the motion
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
- Bugfix: a broken placeholer in a motion-supporting-INPUT

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
- Local translations variants files can be created without commiting them to the repository
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
- Bugfix: a depencency necessary for direct SMTP-support for system e-mails was missing

### Version 3.3.4 (2016-06-11)

- Bugfix: empty lines vanished under some circumstances
- Bugfix: a class name collission in EmailNotifications.php was resolved
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

The changelog vor version 2 can be found at [https://github.com/CatoTH/antragsgruen/blob/v2/History.md](https://github.com/CatoTH/antragsgruen/blob/v2/History.md)

## Version 1.0 (2012)

