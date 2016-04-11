# Version history

## Version 3.2.2 [Not yet released]

- Bugfix: some supporters where tagged with "You!" for logged-out users
- Bugfix: better string normalization

## Version 3.2.1 (2016-04-06)

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
 
This release was mainly sponsored by the [German Federal Youth Council](http://www.dbjr.de/).

## Version 3.0 (2015-11-16)

- Complete rewrite of Antragsgrün

This rewrite was mainly sponsored by the [German Green Party](https://www.gruene.de/).
