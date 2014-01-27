game-job-queue
==============

A job queue with the ability to spread processing the queue to multiple servers


This version was taken from a space game, on which my friend Stu and I were working on a few years ago.

I created this job queue to manage all the various events taking place in this game. All events, as I called them,  should be executed on a specific time. Not before not after.

This queue should be run as a daemon, using PHP.

All data is stored in one central event database. Unfortunately the database structure is still not complete. I will add the sql structure as soon as I restored it.

The project consists of two parts:

1. Server
  
The server should be run on one machine. It accepts events from the client and processes it.
 
2. Client
 
The client takes care of loading events which should be executed at the very second and sends it to the server for processing. The client can be run on one or multiple machines. 
