# dataset_validation
NOTE: You need to download and install the IOOS Compliance checker at https://github.com/ioos/compliance-checker/ and make sure the compliance-checker executable are availavble in the PATH.

## dataset validation form
Provides a form for uploading single netCDF file, or an archive of NetCDF file(s).
Perform the IOOS Compliance checker on the netCDF files using the dataset_validation.compliance_checker service


## dataset_validation.compliance_checker service
Provides a contianer service for checking compliance of netCDF files using IOOS Compliance checker, given filename, and test(s).
Defined by the CompianceChekcerInterface.


## Known problems:
- bz2 archives does not work, even when the drupal archiveManager should support it. An error is sent to user now if cannot process the archive.
