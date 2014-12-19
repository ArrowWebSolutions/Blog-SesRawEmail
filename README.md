#SES Raw Email Sender
This is some basic code to accompany a blog post on [sending raw email with attachments via Amazon SES API](http://www.arrow-web.co.uk/blog/2014/12/sending-raw-email-ses).

It's not well structured code, but shows how to send a raw email via the Amazon SES API.

###Getting it running
Clone the repository.

Run `composer install` from the project directory. (Or use Vagrant if you don't have composer)

Open `index.php`, scroll down to the bottom and change lines 270-273, 276 and 279. You'll need to add your own SES details in here.

You should then be able to run:
	
	php index.php
	
###Running Vagrant
If you don't have PHP installed on your local machine, you can run this in a VM.

The project ships with a Vagrantfile and should provision itself thanks to [Vaprobash](http://fideloper.github.io/Vaprobash/). Just run

	vagrant up
	
From the project directory, let the box provision. Then hit [blog-ses.192.168.22.10.xip.io](blog-ses.192.168.22.10.xip.io) in your web browser.

Any questions let us know:

[Arrow](http://www.arrow-web.co.uk/)

[@Arrow_UK](https://twitter.com/Arrow_UK)
	 