# Symfony 4.* based REST auth basic implementation app includes:
- Docker (php + nginx + postgres + adminer + redis)
- Codeception 
- XDebug
- Postman collection
- [Implementation docs](https://github.com/vavilen84/symfony_4_rest_auth/blob/master/docs/implement_rest_auth.md)

##  Install Docker 

https://docs.docker.com/install/

### Installation on Ubuntu

https://docs.docker.com/install/linux/docker-ce/ubuntu/

```
sudo apt-get install \
    apt-transport-https \
    ca-certificates \
    curl \
    software-properties-common
```

```
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
```

```
sudo add-apt-repository \
       "deb [arch=amd64] https://download.docker.com/linux/ubuntu \
       $(lsb_release -cs) \
       stable"
```

```
sudo apt-get update
```

```
sudo apt-get -y install docker-ce
```

## Create the docker group

```
sudo groupadd docker
sudo usermod -aG docker $USER
```

##  Install docker-compose 

https://docs.docker.com/compose/install/

### Installation on Ubuntu

```
sudo curl -L https://github.com/docker/compose/releases/download/1.19.0/docker-compose-`uname -s`-`uname -m` -o /usr/local/bin/docker-compose
```

```
sudo chmod +x /usr/local/bin/docker-compose
```

##  Install docker-hostmanager

https://github.com/iamluc/docker-hostmanager

### Installation on Ubuntu

https://github.com/iamluc/docker-hostmanager#linux

```
docker run -d --name docker-hostmanager --restart=always -v /var/run/docker.sock:/var/run/docker.sock -v /etc/hosts:/hosts iamluc/docker-hostmanager
```

##  Add ENV file

create .env file from .env.dist file and set correct vars values in it


##  Start with Docker

```
docker-compose up -d --build
```

##  run commands after setup


install composer libs
```
docker exec -it --user 1000 symfony4restauth_php_1 composer install
```

create db schema if not exists
```
docker exec -it --user 1000 symfony4restauth_php_1 bin/console doctrine:schema:create
```

run migrations
```
docker exec -it --user 1000 symfony4restauth_php_1 bin/console doctrine:migrations:migrate
```

run fixtures
```
docker exec -it --user 1000 symfony4restauth_php_1 bin/console doctrine:fixtures:load (required for codeception tests!!!)
```

where symfony4restauth_php_1 php container name

## XDEBUG
set alias 10.254.254.254 to 127.0.0.1 network interface for XDEBUG
```
$ sudo ifconfig lo:0 10.254.254.254 up
```

## URLs:
"http://site.symfony4restauth_local/" - website<br>
"http://adminer.symfony4restauth_local:8080/" - adminer

## Codeception tests
create db schema (if not created yet)
```
$ docker exec -it --user 1000 symfony4restauth_php_1 bin/console doctrine:schema:create 
```

upload fixtures
```
$ docker exec -it --user 1000 symfony4restauth_php_1 bin/console doctrine:fixtures:load 
```

goto container
```
$ docker exec -it --user 1000 symfony4restauth_php_1 bash
$ cd codeception
```
run all tests
```
$ php ../vendor/bin/codecept run tests/
```
run one test 
```
$ php ../vendor/bin/codecept run tests/Api/AuthControllerCest.php --debug
```
