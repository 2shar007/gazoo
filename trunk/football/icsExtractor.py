import pymysql
from datetime import datetime

def icsExtractor(calendarFilePath, subjectTitle, subjectDescription, dateFormat):
  
  configFile = open("db_config")
  for line in configFile.readlines():
    elements = line.rstrip("\n").split(":")
    if (elements[0]=="ip"):
      ip = elements[1]
    elif (elements[0]=="user"):
      user = elements[1]
    elif (elements[0]=="db"):
      db = elements[1]
    elif (elements[0]=="pwd"):
      pwd = elements[1]

  connexion = pymysql.connect(host=ip, user=user, db=db, passwd=pwd)
  cursor = connexion.cursor()
  isSecondEnd = False

  dropSubjectRequest = "DELETE FROM subject WHERE name='" + subjectTitle + "';"
  insertSubjectRequest = "INSERT INTO subject(name, description, category) values ('" + subjectTitle + "','" + subjectDescription + "', 'Football');"

  cursor.execute(dropSubjectRequest)
  cursor.execute(insertSubjectRequest)
  subjectId = connexion.insert_id()

  calendarFile = open(calendarFilePath)
  eventStart = ""
  eventDescription = ""
  eventStart = ""
  eventEnd = ""
  for line in calendarFile.readlines():
    line = line.rstrip("\n").split(":")
    if (line[0] == "BEGIN"):
      isSecondEnd = True
    elif (line[0] == "DTSTART"):
      eventStart = datetime.strftime(datetime.strptime(line[1], dateFormat), '%Y-%m-%d %H:%M:%S')
    elif (line[0] == "SUMMARY"):
      eventName = line[1].replace("\u2019"," ").replace("'"," ")
    elif (line[0] == "DESCRIPTION"):
      eventDescription = line[1].replace("\u2019"," ").replace("'"," ")
    elif (line[0] == "DTEND"):
      eventEnd = datetime.strftime(datetime.strptime(line[1], dateFormat), '%Y-%m-%d %H:%M:%S')
    elif (line[0] == "END"):
      if (isSecondEnd):
        insertEvent = "INSERT INTO event(name, description, start, end) VALUES ('" + eventName + "','" + eventDescription + "','" + eventStart + "','" + eventEnd + "');"
        cursor.execute(insertEvent)
        insertEventSubject = "INSERT INTO subject_event(id_subject, id_event) VALUES (" + str(subjectId) + "," + str(connexion.insert_id()) + ");"
        cursor.execute(insertEventSubject)
      isSecondEnd = False 
