# dataset_validation

## dataset validation form
Provides a form for uploading single netCDF file or archive ofNetCDF files.
Perform the IOOS Compliance checker on the netCDF files using the dataset_validation.compliance_checker service


## dataset_validation.compliance_checker service
Provides a contianer service for checking compliance of netCDF files using IOOS Compliance checker, given filename, and test(s).



## Known problems:
- bz2 archives does not work, even when the drupal archiveManager should support it. An error is sent to user now if cannot process the archive.
