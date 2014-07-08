# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|


  config.berkshelf.enabled = true

  # Use this for public release, and turn on provisioning of chef on vagrant up.
  #config.vm.box = "chef/ubuntu-14.04"

  # Used for local testing, already has chef-client pre-installed.
  config.vm.box = "pressable-ubuntu-14.04"

  config.vm.network :forwarded_port, host: 8844, guest: 80

  config.vm.provision "chef_solo" do |chef|
    chef.add_recipe "anemometer::mariadb"

    # You may also specify custom JSON attributes:
    chef.json = { mysql_password: "foo" }
  end


  config.vm.provision :shell, :path => "vagrant/bootstrap.sh"
end
