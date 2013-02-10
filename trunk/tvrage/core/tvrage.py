#!/usr/bin/env python

#  tvrage.py version Alpha minus
#  Project Kalendy @ Startup Weekend Paris, Feb '13
#
#  @uthors - Tushar Ghosh (2shar007)
#

import sys
import MySQLdb
from datetime import datetime
import urllib, urllib2
from BeautifulSoup import BeautifulSoup

def parse_content(raw_content):
	values = dict()
	try:
		parsed_vals = BeautifulSoup(raw_content)
		if parsed_vals.find("status").contents[0] is "Canceled/Ended":
			values  = None
		else:
			values['name'] = parsed_vals.find("name")
			values['genre'] = parsed_vals.findAll("genre")
			values['airtime'] = parsed_vals.find("airtime")
			values['airdate'] = parsed_vals.findAll("airdate")
	except:
		print "Couldn't parse content :-('"
		pass
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
		pass
	return content

def main(showID):
	record = None
	api = "http://services.tvrage.com/feeds/full_show_info.php"
	values = { 'sid' : showID }
	service_api = api + "?" + urllib.urlencode(values)
	info = get_html(service_api)
	if info is not None:
		record = parse_content(info)
	return record

# calling main for execution
try:
	conn = MySQLdb.connect()
	cursor = conn.cursor(MySQLdb.cursors.DictCursor)
	#do stuff here
	row = None
	now_stamp = int(datetime.now().strftime("%s"))
	for sid in range(34500, 25000, -1):
		name = ""
		genre = ""
		time = ""
		date = ""
		ready = False
		try:
			row = main(str(sid))
			if row is not None:
				name = row['name'].contents[0]
				time = row['airtime'].contents[0]
				for val in row['genre']:
					genre = (val.contents[0] + "," + genre)
				for d in row['airdate']:
					t = None
					time_stamp = None
					try:
						t = (d.contents[0] + " " + time)
						time_stamp = int(datetime.strptime(t, "%Y-%m-%d %H:%M").strftime("%s"))
						if time_stamp >= now_stamp:
							ready = True
							time = datetime.fromtimestamp(time_stamp).strftime("%Y-%m-%d %H:%M")
							break
					except:
						print "Couldn't extract time. Dommage!!!"
						pass
				if ready is True:
					try:
						# sanitize queries for special characters
						insert_event = "INSERT INTO event(name, description, start) VALUES ('" + name + "','" + genre + "','" + time + "')"
						cursor.execute(insert_event)
						print(name + "[" + str(conn.insert_id()) + "]: " + genre + " at " + time)
						insert_event_subject = "INSERT INTO subject_event(id_subject, id_event) VALUES (2, " + str(conn.insert_id()) + ")"
						cursor.execute(insert_event_subject)
					except Exception, e:
						print str(e)
						print "Couldn't commit to database"
						pass
		except Exception, e:
			print str(e)
			print "Main gone rogue ... Exiting!!"
			pass
	cursor.close()
	conn.commit()
	conn.close()
except	MySQLdb.Error, e:
	print "Error: Exiting ..."
	sys.exit(1)
