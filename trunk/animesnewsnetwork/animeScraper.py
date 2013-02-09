from html.parser import HTMLParser
import urllib.request

class AnimeHTMLParser(HTMLParser):

  def __init__(self):
    super().__init__()
    self.title = False

  def handle_starttag(self, tag, attrs):
    if (tag=='title' and self.get_starttag_text()=="Resouce not found"):
      self.close()

    if (tag=='h1'):
      for attribute in attrs:
        if (attribute[0]=='id' and attribute[1]=="page_header"):
          self.title = True

  def handle_data(self, data):
    if (self.title):
          print('subject/name : ' + data)
          self.title = False
    # print("Encoutered a start tag : " + tag)
    # print(attrs)


if __name__ == '__main__':
  parser = AnimeHTMLParser()
  parser.feed(urllib.request.urlopen('http://www.animenewsnetwork.com/encyclopedia/anime.php?id=14089').read().decode('utf-8'))
