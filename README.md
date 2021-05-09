# PodcastRSS

PodcastRSS - generate podcast RSS for some website.

- [INSTALLATION](#installation)
- [USAGE](#usage)
    - [VISTOPIA](#vistopia)
- [FAQ](#faq)

# INSTALLATION

To install it right away for all UNIX users (Linux, macOS, etc.), type:

```shell
git clone git@github.com:yhyy135/PodcastRSS.git && cd PodcastRSS
composer install
composer dump-atoload -o
```

# Usage

PodcastRSS is based on Symfony console, so you can use this command to check all commands:

```shell
php podcastrss list
```

## Vistopia

```shell
php podcastrss generate:vistopia [options]
```

### Options

```
  -i, --url=URL              The url of the show detail page.
  -t, --token[=TOKEN]        The token of vistopia account. [default: ""]
  -s, --shownote[=SHOWNOTE]  The identifier of generating RSS include show notes information. [default: false]
```

### Example

```shell
# Generate the public show RSS without show notes
php podcastrss generate:vistopia -i https://shop.vistopia.com.cn/detail?id=xxxxx

# Generate the public show RSS with show notes
php podcastrss generate:vistopia -i https://shop.vistopia.com.cn/detail?id=xxxxx -s

# Generate the premium show RSS with show notes
php podcastrss generate:vistopia -i https://shop.vistopia.com.cn/detail?id=xxxxx -t 38270bbcb0e011eb85290242ac13000338270bbcb0e011eb85290242ac130003 -s
```

# FAQ

### How do I get vistopia token?

Add this javascript as a new bookmark to Chrome, and you could get vistopia token by clicking this bookmark when visiting the show detail page such as: https://shop.vistopia.com.cn/detail?id=11

```shell
javascript:(function(){var name="user_tk=";var ca=document.cookie.split('; ');for(var i=0;i<ca.length;i++){var c=ca[i].trim();if(c.indexOf(name)==0){alert('You token is:\n'+c.substring(name.length,c.length))}}})()
```
