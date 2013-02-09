from html.parser import HTMLParser
from event import Event
import urllib.request

class AnimeEventHTMLParser(HTMLParser):

  def __init__(self):
    super().__init__()
    self.date = False
    self.predate = False
    self.episodeNumber = False
    self.preepisodeTitle = False
    self.episodeTitle = False
    self.titleBuffer = ""
    self.inEpisodeTable = False
    self.event = Event()

  def handle_starttag(self, tag, attrs):
    if (tag=='title' and self.get_starttag_text()=="Resouce not found"):
      self.close()

    if (tag=='table'):
      for attribute in attrs:
        if (attribute[0]=='class' and attribute[1]=="episode-list"):
          self.inEpisodeTable = True

    if (self.inEpisodeTable and tag=='tr'):
      self.event = Event()

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
        self.event.start = data
        self.event.end = data
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
      self.event.name = self.titleBuffer
      self.anime.events.append(self.event)

    if (self.inEpisodeTable and tag=='table'):
      self.inEpisodeTable = False

  def feedAnime(self, url, anime):
    self.anime = anime
    self.feed(urllib.request.urlopen(url).read().decode('utf-8'))
