from animeScraper import AnimeHTMLParser
from animeEventScraper import AnimeEventHTMLParser

class Anime():
  def __init__(self, parser, eventParser):
    self.title = ""
    self.description = ""
    self.events = []
    self.parser = parser
    self.eventParser = eventParser

  def feed(self, url):
    parser.feedAnime(url, self)
    eventParser.feedAnime(url + "&page=25", self)

  def insert(self):
    print("not implemented")

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
