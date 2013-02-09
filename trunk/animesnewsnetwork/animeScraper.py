from html.parser import HTMLParser
import urllib.request

class AnimeHTMLParser(HTMLParser):

  def __init__(self):
    super().__init__()
    self.title = False
    self.content = False
    self.precontent = False

  def handle_starttag(self, tag, attrs):
    if (tag=='title' and self.get_starttag_text()=="Resouce not found"):
      self.close()

    if (tag=='h1'):
      for attribute in attrs:
        if (attribute[0]=='id' and attribute[1]=="page_header"):
          self.title = True

    if (tag=='div'):
      for attribute in attrs:
        if (attribute[0]=='id' and attribute[1]=="infotype-12"):
          self.precontent = True

    if (self.precontent and tag=='span'):
          self.precontent = False
          self.content = True

  def handle_data(self, data):
    if (self.title):
        self.anime.title = data
        self.title = False
    
    if (self.content):
        self.anime.description = data
        self.content = False

  def feedAnime(self, url, anime):
    self.anime = anime
    self.feed(urllib.request.urlopen(url).read().decode('utf-8'))
