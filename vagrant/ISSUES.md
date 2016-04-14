#

```
Downloading VirtualBox Guest Additions ISO from http://download.virtualbox.org/virtualbox/4.3.36/VBoxGuestAdditions_4.3.36.iso
Copy iso file /home/emanuel/.vagrant.d/tmp/VBoxGuestAdditions_4.3.36.iso into the box /tmp/VBoxGuestAdditions.iso
mount: /dev/loop0 is write-protected, mounting read-only
Installing Virtualbox Guest Additions 4.3.36 - guest version is unknown
Verifying archive integrity... All good.
Uncompressing VirtualBox 4.3.36 Guest Additions for Linux............
VirtualBox Guest Additions installer
Copying additional installer modules ...
Installing additional modules ...
Removing existing VirtualBox non-DKMS kernel modules[  OK  ]
Building the VirtualBox Guest Additions kernel modules
Building the main Guest Additions module[  OK  ]
Building the shared folder support module[  OK  ]
Building the OpenGL support module[  OK  ]
Doing non-kernel setup of the Guest Additions[  OK  ]
Starting the VirtualBox Guest Additions [  OK  ]
Installing the Window System drivers
Could not find the X.Org or XFree86 Window System, skipping.
An error occurred during installation of VirtualBox Guest Additions 4.3.36. Some functionality may not work as intended.
In most cases it is OK that the "Window System drivers" installation failed.
Cleaning up downloaded VirtualBox Guest Additions ISO...
==> default: Checking for guest additions in VM...
==> default: Rsyncing folder: /home/emanuel/vagrant/ => /home/vagrant/sync
==> default: Mounting shared folders...
    default: /vagrant => /home/emanuel/vagrant
==> default: Machine already provisioned. Run `vagrant provision` or use the `--provision`
==> default: flag to force provisioning. Provisioners marked to run always will still run.
```

```
There was an error when attempting to rsync a synced folder.
Please inspect the error message below for more info.

Host path: /home/emanuel/vagrant/
Guest path: /home/vagrant/sync
Command: rsync --verbose --archive --delete -z --copy-links --no-owner --no-group --rsync-path sudo rsync -e ssh -p 2222 -o StrictHostKeyChecking=no -o IdentitiesOnly=true -o UserKnownHostsFile=/dev/null -i '/home/emanuel/vagrant/.vagrant/machines/default/virtualbox/private_key' --exclude .vagrant/ /home/emanuel/vagrant/ vagrant@127.0.0.1:/home/vagrant/sync
Error: Warning: Permanently added '[127.0.0.1]:2222' (ECDSA) to the list of known hosts.
symlink has no referent: "/home/emanuel/vagrant/anemometer/anemometer"
FATAL I/O ERROR: dying to avoid a --delete-during issue with a pre-3.0.7 receiver.
rsync error: requested action not supported (code 4) at flist.c(1885) [sender=3.1.0]
```
