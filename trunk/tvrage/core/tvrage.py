#!/usr/bin/env python

#  tvrage.py version Alpha minus
#  Project Gazoo @ Startup Weekend Paris, Feb '13
#
#  @uthors - Tushar Ghosh (2shar007)
#

import sys
import MySQLdb
import datetime
import urllib2, urllib
from BeautifulSoup import BeautifulSoup

# Class Gazer
class Gazer():
	
	def __init__(self, data_instance = None):
		if data_instance is None:
			self.data_instance = ""
		else:
			self.data_instance = data_instance
	
	def parse_info(self):
		instance = BeautifulSoup(data_instance)
		print instance.show.showid()
		return
	
	def get_info(self, api_instance):
		""""""
		urllib2.socket.setdefaulttimeout(10)
		try:
			handler = urllib2.urlopen(api_instance)
		except:
			print "Could not process API request. Exiting ..."
			sys.exit(1)
		data_instance = handler.read()
		print handler.read()
		content = self.parse_info()
		handler.close()
		return content
		
# end of class Gazer

def main():
	record = ""
	api = "http://services.tvrage.com/feeds/search.php"
	values = { 'show' : 'buffy' }
	service_api = api + "?" + urllib.urlencode(values)
	bot = Gazer()
	info = bot.get_info(service_api)
	# record = structure info into tuples
	#cursor.execute()
	return

# calling main for execution
try:
	#conn = MySQLdb.connect()
	#cursor = conn.cursor(MySQLdb.cursors.DictCursors)
	#do stuff here
	try:
		main()
	except:
		print "Main gone rogue ... Oops!!"
	#cursor.close()
	#conn.commit()
	#conn.close()
except	MySQLdb.Error, e:
	print "Error: Exiting ..."
	sys.exit(1)

	
