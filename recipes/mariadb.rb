
include_recipe 'apt'



apt_repository 'mariadb' do
  uri          'http://ftp.osuosl.org/pub/mariadb/repo/10.0/ubuntu'
  distribution node['lsb']['codename']
  components   ['main']
  keyserver    'keyserver.ubuntu.com'
  key          '0xcbcb082a1bb943db'
end


package "mariadb-server"
package "percona-toolkit"

package "libapache2-mod-php5"
package "php5-mysqlnd"
