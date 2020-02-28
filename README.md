# Firefly III bunq importer
A Firefly III importer that can get files from bunq. Work in progress.

## WoW
Should work similar to the CSV importer.

### Config
Config requires: bunq api token, bunq url (to use sandbox), personal access token.

Can also use import config, similar to the CSV importer for advanced config.

### Web interface
First: select either date range, "everything", or today-X days.

Also configure: import specific meta data. Use the tags from bunq? Depends on what the API can give us. Apply rules? Stuff like that.

Select / filter accounts to import from. Cannot import if asset does not exist (IBAN match is mandatory). User must create these themselves? Or do it for them?

After this, importer will download the selection from bunq and convert if necessary.

Optional step: mapping. Link found expense accounts etc to Firefly III entries. Check out multi-currency transactions. May need users to help me out.

Then just save it all. Offer JSON file for future imports.

## Todo
- How to handle "auto save money" feature from bunq?
