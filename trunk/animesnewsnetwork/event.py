class Event():
  def __init__(self):
    self.name = ""
    self.start = ""
    self.end = ""

  def show(self):
    print("event/name: " + self.name)
    print("event/start: " + self.start)
    print("event/end: " + self.end)
