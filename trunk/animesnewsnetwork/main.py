import pymysql
from animeEventScraper import AnimeEventHTMLParser
from animeScraper import AnimeHTMLParser
from anime import Anime
from event import Event


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
baseUrl = 'http://www.animenewsnetwork.com/encyclopedia/anime.php?id='

parser = AnimeHTMLParser()
eventParser = AnimeEventHTMLParser()

begin = 14723
end = 14724
for i in range(begin,end):
  print(str(i-begin+1))
  anime = Anime(parser, eventParser)
  anime.feed(baseUrl + str(i))
  anime.insert(connexion)
