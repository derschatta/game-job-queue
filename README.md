game-job-queue
==============

A job queue with the ability to spread processing the queue to multiple servers


This version was taken from a space game, on which I worked on with a friend a number of years ago. We eventually stopped the project. At one point I got nostalgic and digged the code up and put it up here.

It's pretty old school code which I wouldn't write in the same way today anymore. Even so I enjoyed working on the challenge of creating a queue and make sure it can processs as many events as possible as close as possible to the desired time. Also scaling was one thing I tried to tackle by making it possible to run it on multiple processes in parallel. Today you would use a proper queueing software like RabbitMQ and for performance reasons you probably wouldn't pick PHP but Go or something similar. Well, I learned a lot by writing it more or less from scratch.

I created this job queue to manage all the various events taking place in this game. All events, as I called them, should be executed on a specific time. Not before not after.

This queue should be run as a daemon, using PHP.

All data is stored in one central event database. Unfortunately the database structure is still not complete. I will add the sql structure as soon as I restored it.

The project consists of two parts:

1. SERVER
  
The server should be run on one machine. It accepts events from the client and processes it.
 
2. CLIENT
 
The client takes care of loading events which should be executed at the very second and sends it to the server for processing. The client can be run on one or multiple machines. 
