=======================================
Question2Answer Book
=======================================
-----------
Description
-----------
This is a plugin for **Question2Answer** that creates an html e-book of the sites top questions and answers.

--------
Features
--------
- fully customizable HTML template via admin/plugins
- options for sorting, inclusion exclusion filters, include/exclude answers
- option for static or on-the-fly creation (static requires PHP to write to file)
- option to create PDF file - requires wkhtmltopdf (see below)
- optional widget for displaying download links in sidebar

------------
Installation
------------
#. Install Question2Answer_
#. Get the source code for this plugin from github_, either using git_, or downloading directly:

   - To download using git, install git and then type 
     ``git clone git://github.com/arjunsuresh/q2a-book.git book``
     at the command prompt (on Linux, Windows is a bit different)
   - To download directly, go to the `project page`_ and click **Download**

#. navigate to your site, go to **Admin -> Plugins** on your q2a install, go to the "Book" panel, select options, click "Create".

.. _Question2Answer: http://www.question2answer.org/install.php
.. _git: http://git-scm.com/
.. _github:
.. _project page: https://github.com/arjunsuresh/q2a-book

------------
Static Files
------------

To output static files you need to find a location that is writeable by PHP.  The default is to write to the plugin dir itself, which is probably not writeable.  On Linux, something like this works:

  touch book.html
  touch book.pdf
  chmod 777 book.html book.pdf

------------
PDF File
------------

To use the PDF export, you need to put the binary of wkhtmltopdf, available here:

http://code.google.com/p/wkhtmltopdf/downloads/list

in the same directory as these plugin files.  It works for Linux 64-bit, no guarantees apart from that.

----------
Disclaimer
----------
This is **beta** code.  It is probably okay for production environments, but may not work exactly as expected.  Refunds will not be given.  If it breaks, you get to keep both parts.

-------
Release
-------
All code herein is Copylefted_.

.. _Copylefted: http://en.wikipedia.org/wiki/Copyleft

---------
About q2A
---------
Question2Answer is a free and open source platform for Q&A sites. For more information, visit:

http://www.question2answer.org/

