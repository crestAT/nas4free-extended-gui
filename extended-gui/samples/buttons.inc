<?php 
/*
    buttons.inc

    Sample file for Extended GUI

    Copyright (c) 2016 - 2017 Andreas Schmidhuber <info@a3s.at>
    All rights reserved.

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice, this
       list of conditions and the following disclaimer.
    2. Redistributions in binary form must reproduce the above copyright notice,
       this list of conditions and the following disclaimer in the documentation
       and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
    ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
    WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
    DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
    ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
    (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
    LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
    ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
    (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
    SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

    The views and conclusions contained in the software and documentation are those
    of the authors and should not be interpreted as representing official policies,
    either expressed or implied, of the FreeBSD Project.
*/

/*
Actions that will be performed after buttons have been clicked, in this simple example they just write one line to the system log 
*/
if ($_POST) {
	if (isset($_POST['start_plex_example']) && $_POST['start_plex_example']) {
		mwexec("logger Plex Media Server started!");
	}
	if (isset($_POST['stop_plex_example']) && $_POST['stop_plex_example']) {
		mwexec("logger Plex Media Server stopped!");
	}
}
?>    

<!-- Buttons will be shown at the end of the Status > System page -->
<div id="submit">
	<input name="start_plex_example" type="submit" class="formbtn" title="<?=gettext("Start Plex Media Server");?>" value="<?=gettext("Start Plex Example");?>" />
	<input name="stop_plex_example" type="submit" class="formbtn" title="<?=gettext("Stop Plex Media Server");?>" value="<?=gettext("Stop Plex Example");?>" />
</div>
