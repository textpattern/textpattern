Unlimited Custom Fields in development
======================================

WARNINGS

* DO NOT TRY THIS BRANCH ON A SITE THAT EMPLOYS glz_custom_fields.
  DOING SO WILL RESULT IN DATA LOSS. AN EXTRA PLUGIN (NOT YET
  WRITTEN) IS REQUIRED TO SAFELY MIGRATE glz DATA ACROSS.

* Backup everything first, this is NOT production ready.

* Existing custom field data from the Textpattern table will be migrated
  to new tables and old data WILL BE DELETED if possible. The migration
  routine is not fully tested yet and may not work properly.

* This is seriously experimental code.


ROADMAP

* Iron out kinks in Article admin-side implementation, particularly
  the @Todo entries in the code.

* Roll out fields across other content types:
** Images
** Files
** Links
** Users
** Categories
** Sections (requires more table mods as it doesn't have an ID column)

* Refactor public side tags to access new types.

* Beef up conditional tags.

* Consider permitting access to the render() widgets code so input
  fields of the correct type can be drawn.

* Fiddle with jQuery UI to enable drag 'n drop of field orders.
** Store as per-user pref?
** Per-Section for Articles?

* Write plugin to migrate non-text_input data types from glz_custom_fields.
** Hook into a new pre-upgrade callback? Probably just needs to provide a mapping
   of glz's data type names to Txp's data_type names. Then, when such columns are
   encountered during upgrade, Textpattern\Meta\Field()'s save routine can
   create the tables of the relevant types and pull data into them.

