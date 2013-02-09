#!/usr/bin/env python

#  tvrage.py version Alpha minus
#  Project Kalendo @ Startup Weekend Paris, Feb '13
#
#  @uthors - Tushar Ghosh (2shar007)
#

import sys
import MySQLdb
import datetime
import urllib, urllib2
from random import randint
from BeautifulSoup import BeautifulSoup

def parse_content(raw_content):
	""""""
	values = None
	try:
		parsed_vals = BeautifulSoup(raw_content)
		name = parsed_vals.findAll("name")
		genre = parsed_vals.findAll("genre")
		print name + ": " + genre
	except:
		print "Couldn't parse content :-('"
	return values

def get_html(URL):
	content = None
	try:
		urllib2.socket.setdefaulttimeout(10)		#	maximum request time
		URLdata = urllib2.urlopen(URL)		#	stores info about URL and if there is redirect
		URL = URLdata.url						#	stores redirected or parent URL
		content = URLdata.read()				#	read html data from object
		URLdata.close()								#	terminate connection
	except:
		print "Oops!! Couldn't retrieve content"
		sys.exit(1)
	return content

def main(showID):
	record = None
	api = "http://services.tvrage.com/feeds/full_show_info.php"
	values = { 'sid' : showID }
	service_api = api + "?" + urllib.urlencode(values)
	info = get_html(service_api)
	record = parse_content(info)
	return record

# calling main for execution
try:
	#conn = MySQLdb.connect()
	#cursor = conn.cursor(MySQLdb.cursors.DictCursors)
	#do stuff here
	try:
		#for loop here
		sid = randint(1,3000)
		tuple = main(str(sid))
	except:
		print "Main gone rogue ... Exiting!!"
	#cursor.close()
	#conn.commit()
	#conn.close()
except	MySQLdb.Error, e:
	print "Error: Exiting ..."
	sys.exit(1)

	
