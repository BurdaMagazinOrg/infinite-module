# Odoscope export and upload

## Inintial export

The initial export for odoscope has to be done manually by doing the
follwing steps.

 1. Generating the export file
 2. Uploading file to odoscope

### Generating the export file

**The generation of the initial export file can take a long time.**

The following command will generete a CSV export file in the private
files directory. For the PROD environment this
is `/mnt/gfs/instyleweb.prod/files-private`. The name of the file
ist something like `0113224140774InStyle-article-export.csv`.

    drush views-data-export odoscope_article_export data_export_1 -l http://www.instyle.de

**In case of failure.**

The command can take a long time to complete. It is possible, that it
does not complete at all. You may get an error message `the MySQL server has gone away`.

In that case you should try again at a later time, when the server has
less load. If it still does not succeed, you can switch on views UI
and change the filter value `less than 2 years` to `less than 1 year`
to get less articles in the first place. This has been appropved by Harm.

### Uploading file to odoscope

Before uploading the export file, it should be gzipped to reduce the
uploading time.

    cd /mnt/gfs/instyleweb.prod/files-private
    gzip 0113224140774InStyle-article-export.csv

The uploading of the initial export file is done by `curl`.

    curl --basic -u <odoscope_user>:<odoscope_pass> -X POST <odoscope_url> -F "file=@<csv_export_file_path>" -H "Content-Type: multipart/form-data"

Please replace the tokens `<odoscope_user>`, `<odoscope_pass>`
and `<odoscope_url>` by values which you can find in
settings.\<env\>.php. The token `<csv_export_file_path>` must be
replaced by the absolute path of the gzipped export file
(e.g `/mnt/gfs/instyleweb.prod/files-private/0113224140774InStyle-article-export.csv.gz`).

## Delta export

The odoscope delta export is running as a cron job on the PROD environment.
It is configured in Acquia Cloud under "Scheduled jobs" to run every 15 minutes
with the following command.

    drush @instyleweb.prod -u 1 -l http://www.instyle.de --strict=0 odoscope-queue &>> /var/log/sites/${AH_SITE_NAME}/logs/$(hostname -s)/odoscope-delta-export.log

After the cron job execution the uploaded export file can be found in
`/mnt/gfs/instyleweb.prod/files-private/odoscope-archive`.