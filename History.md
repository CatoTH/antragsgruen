# Version history

## Version 4.7.0 [not released yet]

- The pink deadline circle on the consultation is now also shown if multiple motion types with the same deadline exist.
- First, small beginnings of a REST API are implemented, currently with read-only access. It can be enabled in the site component settings. The documentation can be found at [docs/openapi.yaml](docs/openapi.yaml)
- Internal: the login system now supports plugins for retrieving user accounts from external sources, e.g. CMS systems with an existing user database. As an example, a integration into Drupal/CiviCRM can be found in the plugins/drupal_civicrm-folder.
- Internal: Plugins can now provide custom amendment numberings and add extra settings and data fields for amendments and motions.

### Version 4.6.2 [not released yet]

- Motion types that do not have a text part, for example PDF-uploaded applications or financial reports, can now also have comments, if the permission is set accordingly in the motion type settings.
- SVG images can now be uploaded as logo.
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
- It is now easier to edit the agenda of a consultation, as the changes are immediatelly saved and it is not necessary anymore to explicitly use the Save-Button at the bottom of the agenda.
- If a new amendment is screened / published while a new version of the motion is being created (by merging other amendments into it), this new amendment will automatically appear in the merging view. A small hint will appear, notifiying about this addition, and with a link to the first paragraph affected by this amendment. The new amendment is inactive, by default, and needs to be actively activated for merging into the affected paragraph. Vice-versa, if an amendment is unpublished while merging, the amendment hint vanishes automatically. Already applied changes remain, though.
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
- If there is a global password set for a consultation and it is still possible to log in as an user (e.g. as admin), the e-mail-based login form is now a bit more hidden, as the focus will be on the global password for most regular users.
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
- The merging of amendments into motion has greatly been improved. Instead of generating one big, monolithic text with all changes and conflicts, the motion is now split into different paragraphs. For each paragraph, the amendments to be merged into the motion can be selected separately and on-the-fly while editing the new vesion of the text.
- Please note that previous merging drafts that were not completed, are not accessible anymore after upgrading to this release due to incompatibilities with this new merging system.
- The inline amendment mode in the regular motion view (using the green bookmarks at the right) can now be used on devices with small screens as well.
- Several styling improvements, especially for devices with small screens (e.g. smartphones).
- The "My motions" and "My amendments" sections on the consultation overview now list all motions/amendments that one has created, ininiated or expressed (dis)like towards. Not only the ones ininiated as before. This makes it easier to check on motions/amendments that are for some reason not visible anymore on this site (withdrawn, merged, ...)
- The "Using Antragsgrün"-hint in the sidebar can now be disabled through the admin interface.
- Several performance improvements for large installations with hundreds of amendments, especially in the motion view.
- It is now possible to set a detailed voting result for motions and amendments, either from the backend when editing the motion/amendment, or when merging amendments into a motion when confirming the new motion or resultion.
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
- Administrators can now specifiy if only natural persons, only organizations or both can submit new motions and amendments. Default is both.
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
- When administering the list of users eligible to access a consultation, it is now possible to completely remove an user again.
- System administrators can now delete user accounts from the site-wide user list.
- Bugfix: the date of the last saved draft when merging motions was not set correctly on Safari.

## Version 4.1.0 (2018-11-17)

- Several improvements regarding applications, especially the generated PDFs:
  - Motion types can now force motion titles to have a certain beginning, like "Application: ".
  - A new PDF template is introduced specifically for applications, if the LaTeX-based PDF-renderer is used.
  - For each section of a motion type, it is now possible to specify if the title will be explicitly printed in the PDF of not.
  - If the uploaded image is way too big (bigger than 1000x2000px), it is resized to keep the size of the PDF at a reasonable size.
- Two new statuses are introduced: "Resoluton" and "Resolution (preliminary)". Motions of these states...
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
- Bugfix: When submitting an application containing a photo and the admin enabled submission confirmations by e-mail, the confirmation e-mail did not corretly link to the image.
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

