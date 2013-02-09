from animeScraper import AnimeHTMLParser
from animeEventScraper import AnimeEventHTMLParser
import pymysql
from datetime import datetime

class Anime():
  def __init__(self, parser, eventParser):
    self.title = ""
    self.description = ""
    self.events = []
    self.parser = parser
    self.eventParser = eventParser

  def feed(self, url):
    self.parser.feedAnime(url, self)
    self.eventParser.feedAnime(url + "&page=25", self)

  def insert(self, connexion):

    isThereRequest = "SELECT COUNT(*) FROM subject WHERE name='" + self.title + "';"
    insertAnime = "INSERT INTO subject(name, description) values ('" + self.title.replace("\u2019"," ").replace("'"," ") + "','" + self.description.replace("\u2019", " ").replace("'"," ") + "');"
    
    if (len(self.events)>0 and self.title!=""):
      cursor = connexion.cursor()
      cursor.execute(isThereRequest)
      if (cursor.fetchone()[0] == 0):
        cursor.execute(insertAnime)
        animeId = connexion.insert_id()
        for event in self.events:
          start = datetime.strptime(event.start, "%Y-%m-%d")
          end = datetime.strptime(event.end, "%Y-%m-%d")
          insertEvent = "INSERT INTO event(name, start, end) VALUES ('" + event.name.replace("\u2019"," ").replace("'"," ") + "','" + datetime.strftime(start, '%Y-%m-%d %H:%M:%S') + "','" + datetime.strftime(end, '%Y-%m-%d %H:%M:%S') + "');"
          cursor.execute(insertEvent)
          insertEventSubject = "INSERT INTO subject_event(id_subject, id_event) VALUES (" + str(animeId) + "," + str(connexion.insert_id()) + ");"
          cursor.execute(insertEventSubject)

  def show(self):
    print("subject/name: " + self.title)
    print("subject/description: " + self.description)
    print("=== Events ===")
    for element in self.events:
      element.show()
      print("--")

if __name__ == "__main__":
  parser = AnimeHTMLParser()
  eventParser = AnimeEventHTMLParser()
  shinsekai = Anime(parser, eventParser)
  shinsekai.feed('http://www.animenewsnetwork.com/encyclopedia/anime.php?id=14089')
  shinsekai.show()
