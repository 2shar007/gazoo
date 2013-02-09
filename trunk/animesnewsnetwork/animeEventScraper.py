from html.parser import HTMLParser
import urllib.request

class AnimeHTMLParser(HTMLParser):

  def __init__(self, animeId):
    super().__init__()
    self.date = False
    self.predate = False
    self.episodeNumber = False
    self.preepisodeTitle = False
    self.episodeTitle = False
    self.titleBuffer = ""
    self.inEpisodeTable = False
    self.animeId = animeId

  def handle_starttag(self, tag, attrs):
    if (tag=='title' and self.get_starttag_text()=="Resouce not found"):
      self.close()

    if (tag=='table'):
      for attribute in attrs:
        if (attribute[0]=='class' and attribute[1]=="episode-list"):
          self.inEpisodeTable = True

    if (self.inEpisodeTable and tag=='td'):
      for attribute in attrs:
        if (attribute[0]=='class' and attribute[1]=="d"):
          self.predate = True
        if (attribute[0]=='class' and attribute[1]=="n"):
          self.episodeNumber = True

    if (self.predate and tag=='div'):
      self.date = True
      self.predate = False

    if (self.preepisodeTitle and tag=='div'):
      self.episodeTitle = True
      self.preepisodeTitle = False

  def handle_data(self, data):
    if (self.date):
        print('event/date : ' + data)
        self.episodeTitle = False
        self.date = False

    if (self.episodeNumber):
      self.titleBuffer = data
      self.episodeNumber = False
      self.preepisodeTitle = True

    if (self.episodeTitle):
      self.titleBuffer = self.titleBuffer + " " + data
      self.episodeTitle = False
    
  def handle_endtag(self, tag):
    if (self.inEpisodeTable and tag == 'tr'):
      print("event/name : " + self.titleBuffer)

    if (self.inEpisodeTable and tag=='table'):
      self.inEpisodeTable = False


if __name__ == '__main__':
  parser = AnimeHTMLParser(1)
  parser.feed(urllib.request.urlopen('http://www.animenewsnetwork.com/encyclopedia/anime.php?id=14089&page=25').read().decode('utf-8'))
