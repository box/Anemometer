## Migrated to Centos7

Reference:
https://github.com/mitchellh/vagrant/issues/6497


Added in the `Vagrantfile` the following line:

```
config.vm.synced_folder ".", "/vagrant"
```



Requirements:

```
vagrant plugin install vagrant-vbguest
```

## How to setup the vagrant machine

```
mkdir vagrant
git clone git@github.com:3manuek/Anemometer.git anemometer
ln -s anemometer/vagrant/Vagrantfile Vagrantfile
ln -s anemometer/vagrant/bootstrap.sh bootstrap.sh
vagrant up
```
