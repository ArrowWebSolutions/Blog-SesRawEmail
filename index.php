<?php

require_once 'vendor/autoload.php';

use Aws\Ses\SesClient;
use Aws\Ses\Exception\MessageRejectedException;


class SesRawMailer
{
	/*
	 * Our SES client
	 */
	protected $sesClient;

	/**
	 * Create a new mailer
	 * @param SesClient $sesClient The SES Client to send via
	 */
	public function __construct(SesClient $sesClient)
	{
		$this->client = $sesClient;
	}

	/**
	 * Getter for the ses client
	 * @return SesClient our client
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * Sends out a raw email and returns the message id on success
	 * @param  string $to          The recipients email address
	 * @param  string $subject     The subject of the email
	 * @param  string $message     The plain text version of the email
	 * @param  string $from        The senders address
	 * @param  string $html        The HTML version of the email
	 * @param  array  $attachments The full file path of all the attachments
	 * @param  string $returnPath  The return path of the email, although in reality this is ignored in favour of from?
	 * @return string              The message id returned from Amazon
	 */
	public function send($to, $subject, $message, $from, $html = '', $attachments = array(), $returnPath = '')
	{
		if ($returnPath)
		{
			$returnPath = <<<EOF

Return-Path: {$returnPath}
EOF;
		}

		//Create a random boundary
		$boundary = sha1(rand() . time() . 'Arron');

		//lets get started, the headers come first. Pay attention to the blank lines, they are important.
		//Following the headers is our first part of the email. The plain text version.
		$rawEmail = <<<EOE
Subject: {$subject}
MIME-Version: 1.0
Content-type: multipart/alternative; boundary="{$boundary}"{$returnPath}
To: {$to}
From: {$from}

--{$boundary}
Content-Type: text/plain;

{$message}


EOE;
		//if we have some html set, lets create a new part and add it
		if ($html)
		{
			$rawEmail .= <<<EOE
--{$boundary}
Content-Type: text/html; charset=iso-8859-1

{$html}


EOE;
		}

		//loop through our attachments
		foreach ($attachments as $attachment)
		{
			//ensure we can access the file
			if (file_exists($attachment))
			{
				//get all the meta information we need
				$contentType = $this->mimeType($attachment);
				$size = filesize($attachment);
				$attachmentName = basename($attachment);
				//base64 encode our attachment content
				$attachmentContent = base64_encode(file_get_contents($attachment));
				
				$rawEmail .= <<<EOE
--{$boundary}
Content-Type: {$contentType}; name="{$attachmentName}"
Content-Description: "{$attachmentName}"
Content-Disposition: attachment; filename="{$attachmentName}"; size={$size};
Content-Transfer-Encoding: base64

{$attachmentContent}

EOE;
			}
		}

		//finish off our email with the boundary
		$rawEmail .= <<<EOE
--{$boundary}--
EOE;

		//set up the arguments to pass to the client. You can set 'Source' in
		//here, but I encountered errors. So found setting it in the headers worked
		//best.
		$args = array(
			'RawMessage' => array(
				'Data' => base64_encode($rawEmail),
			),
		);

		try
		{
			$response = $this->getClient()->sendRawEmail($args);
			return $response->get('MessageId');
		}
		catch (MessageRejectedException $mrEx)
		{
			$this->log('Unable to send email: Rejected. ' . $mrEx->getMessage());
		}
		catch (\Exception $ex)
		{
			$this->log('Unable to send email: Unknown. ' . $ex->getMessage());
		}

		return false;
	}

	/**
	 * Really bad method to log stuff. Just a proof of concept really. 
	 * You should definately replace this with a decent log.
	 * 
	 * @param  string $message the message to log
	 */
	protected function log($message)
	{
		echo '<p class="error">' . strip_tags($message) . '</p>';
	}

	/**
	 * Simple, method to get mime type based on extension. Again, you really
	 * want to replace this with something better.
	 * 
	 * @param  string $file the full path to the file
	 * @return string       the mime type, or null on failure
	 */
	protected function mimeType($file)
	{
		if (file_exists($file) && $extension = pathinfo($file, PATHINFO_EXTENSION))
		{
			//taken from https://github.com/laravel/laravel/blob/3.0/application/config/mimes.php
			$mimes = array(
				'hqx'   => 'application/mac-binhex40',
				'cpt'   => 'application/mac-compactpro',
				'csv'   => array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream'),
				'bin'   => 'application/macbinary',
				'dms'   => 'application/octet-stream',
				'lha'   => 'application/octet-stream',
				'lzh'   => 'application/octet-stream',
				'exe'   => array('application/octet-stream', 'application/x-msdownload'),
				'class' => 'application/octet-stream',
				'psd'   => 'application/x-photoshop',
				'so'    => 'application/octet-stream',
				'sea'   => 'application/octet-stream',
				'dll'   => 'application/octet-stream',
				'oda'   => 'application/oda',
				'pdf'   => array('application/pdf', 'application/x-download'),
				'ai'    => 'application/postscript',
				'eps'   => 'application/postscript',
				'ps'    => 'application/postscript',
				'smi'   => 'application/smil',
				'smil'  => 'application/smil',
				'mif'   => 'application/vnd.mif',
				'xls'   => array('application/excel', 'application/vnd.ms-excel', 'application/msexcel'),
				'ppt'   => array('application/powerpoint', 'application/vnd.ms-powerpoint'),
				'wbxml' => 'application/wbxml',
				'wmlc'  => 'application/wmlc',
				'dcr'   => 'application/x-director',
				'dir'   => 'application/x-director',
				'dxr'   => 'application/x-director',
				'dvi'   => 'application/x-dvi',
				'gtar'  => 'application/x-gtar',
				'gz'    => 'application/x-gzip',
				'php'   => array('application/x-httpd-php', 'text/x-php'),
				'php4'  => 'application/x-httpd-php',
				'php3'  => 'application/x-httpd-php',
				'phtml' => 'application/x-httpd-php',
				'phps'  => 'application/x-httpd-php-source',
				'js'    => 'application/x-javascript',
				'swf'   => 'application/x-shockwave-flash',
				'sit'   => 'application/x-stuffit',
				'tar'   => 'application/x-tar',
				'tgz'   => array('application/x-tar', 'application/x-gzip-compressed'),
				'xhtml' => 'application/xhtml+xml',
				'xht'   => 'application/xhtml+xml',
				'zip'   => array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
				'mid'   => 'audio/midi',
				'midi'  => 'audio/midi',
				'mpga'  => 'audio/mpeg',
				'mp2'   => 'audio/mpeg',
				'mp3'   => array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
				'aif'   => 'audio/x-aiff',
				'aiff'  => 'audio/x-aiff',
				'aifc'  => 'audio/x-aiff',
				'ram'   => 'audio/x-pn-realaudio',
				'rm'    => 'audio/x-pn-realaudio',
				'rpm'   => 'audio/x-pn-realaudio-plugin',
				'ra'    => 'audio/x-realaudio',
				'rv'    => 'video/vnd.rn-realvideo',
				'wav'   => 'audio/x-wav',
				'bmp'   => 'image/bmp',
				'gif'   => 'image/gif',
				'jpeg'  => array('image/jpeg', 'image/pjpeg'),
				'jpg'   => array('image/jpeg', 'image/pjpeg'),
				'jpe'   => array('image/jpeg', 'image/pjpeg'),
				'png'   => 'image/png',
				'tiff'  => 'image/tiff',
				'tif'   => 'image/tiff',
				'css'   => 'text/css',
				'html'  => 'text/html',
				'htm'   => 'text/html',
				'shtml' => 'text/html',
				'txt'   => 'text/plain',
				'text'  => 'text/plain',
				'log'   => array('text/plain', 'text/x-log'),
				'rtx'   => 'text/richtext',
				'rtf'   => 'text/rtf',
				'xml'   => 'text/xml',
				'xsl'   => 'text/xml',
				'mpeg'  => 'video/mpeg',
				'mpg'   => 'video/mpeg',
				'mpe'   => 'video/mpeg',
				'qt'    => 'video/quicktime',
				'mov'   => 'video/quicktime',
				'avi'   => 'video/x-msvideo',
				'movie' => 'video/x-sgi-movie',
				'doc'   => 'application/msword',
				'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'word'  => array('application/msword', 'application/octet-stream'),
				'xl'    => 'application/excel',
				'eml'   => 'message/rfc822',
				'json'  => array('application/json', 'text/json'),
			);

			if (array_key_exists($extension, $mimes))
			{
				return (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];
			}
		}
	}
}

//Consider replacing this with the newer credential profile
$client = SesClient::factory(array(
	'region' => 'eu-west-1', //replace with your own region
	'key' => 'Your key', //replace with your own key
	'secret' => 'Your secret', //as you guessed, replace with your own secret
));

$to = 'hello@arrow-web.co.uk';
$subject = 'Testing the blog';
//You'll need to be able to send as this address in SES
$from = 'sender@example.com';
//our plain text message
$message = <<<EOF
Hi Arron,

Just testing the class works that you posted on the blog.

Cheers

Dave
EOF;
//our html message
$html = <<<EOF
<html>
<body>
<p><strong>Hi Arron,</strong></p>
<p>Just testing the class works that you posted on the <a href="http://www.arrow-web.co.uk/">blog</a>.</p>
<p>Cheers</p>
<p>Dave</p>
</body>
</html>
EOF;
//our attachments - these are relative to the current file
$attachments = array(
	'test-attachment1.pdf',
	'test-attachment2.png',
);

$mailer = new SesRawMailer($client);
$messageId = $mailer->send($to, $subject, $message, $from, $html, $attachments);

if ($messageId)
{
	echo "<p>Successfully sent message: " . strip_tags($messageId) . "</p>";
}
else
{
	echo "<p>Something went wrong. :(  (check the logs)</p>";
}