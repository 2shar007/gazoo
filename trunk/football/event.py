class Event():
  def __init__(self):
    self.name = ""
    self.start = ""
    self.end = ""
    self.description = ""

  def show(self):
    print("event/name: " + self.name)
    print("event/start: " + self.start)
    print("event/end: " + self.end)
    print("event/description: " + self.description)
