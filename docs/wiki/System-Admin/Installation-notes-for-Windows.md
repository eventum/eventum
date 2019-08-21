## Allowing file uploads

Make sure the IIS username can write to the directory specified in your upload_tmp_dir directive in PHP.INI. If you haven't specified this, it's probably %WINDIR%\\TEMP (C:\\WINNT\\TEMP in my case).

If this permission is not set, file uploads will fail. If uploading anonymously through anonymous issue reporting, there will be no signs of problems in the eventum/logs folder nor in Event Viewer. If uploading through the administrative interface, there will be a simple error message in the browser file upload popup window that doesn't really help figuring out what's wrong.

## Setting up Windows Task Scheduler (for sending email queue or downloading new emails)

This is how I setup jobs to run under Windows XP to process the mail queue or download mail.

-   Create a new scheduled task (process_mail_queue).
-   Under the "Task" tab, set the following values (adjust to match your PHP and Eventum paths).

    -   Run: C:\\php4\\cli\\php.exe -f c:/eventum/misc/process_mail_queue.php
    -   Start in: c:\\eventum\\misc\\

-   Under the "Schedule" tab, set the task to run daily (any time will do) and click "Advanced".

    -   Check the box next to "Repeat Task" and set it to run every 5 minutes(or an interval of your choosing).
    -   Set the duration to be 24 hours.
    -   Click OK

-   Click OK

Your task should now be scheduled run. Repeat to setup the cron to download mail if needed.

## Solving 'CGI Timeout'

I had a problem on the eventum setup it displayed the error "CGI Timeout". The problem was on Windows 2003 using IIS6 with PHP4

To solve the problem:

1.  Install the PHP4 with the windows installer, the copy the extensions provides in PHP 4.4.1 zip package <http://www.php.net/get/php-4.4.1-Win32.zip/from/a/mirror>
2.  On the IIS Manager choose the "web services extensions" option.
3.  Now right click over "All Unknown CGI Extensions" and select "Allow" do the same for "All Unknown CGI Extensions".
4.  Then in the PHP.ini look for " ;extension=php_gd2.dll " and delete the " ; ", this allows PHP to run the GD extension to create graphics.
5.  On the "Windows Environment variables" look for path and add ";c:\\php\\dlls;c:\\php\\extensions;" at the end of the string

This should work to solve this problem.

## Solving 'pages do not refresh'

If you are having trouble that Eventum doesn't respond to changes you make in template source files (\*.tpl.html) files. Make sure that the template_c directory has read/write to IIS_WPG.
