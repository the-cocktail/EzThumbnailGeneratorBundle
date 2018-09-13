# EzThumbnailGeneratorBundle

## 1- Intro

    EzThumbnailGeneratorBundle generates thumnails for a specific ContentType via Command.

## 2- Installation

``` 
    composer require the-cocktail/EzThumbnailGeneratorBundle
```

## 3- Usage

    First of all we need to install an image postprocesor. In this example we'll use optipng.

    This bundle works with the image variaton component of eZ.
    
    So then we have to configure the aliases that we need to use. 
    
````yaml
ezpublish:
    system:
        sites:
           medium:
               reference: reference
               filters:
                   geometry/scaledownonly: [200, 200]
               post_processors:
                   optipng: { strip_all: true, level: 7 }
````
    Next the command execution. It has three mandatory parameters 
        1 - The eZ ContenType that we go to use. Something like an User.
        2 - A comma separated string, without whitespaces, where we'll specify the aliases we want to use.
        3 - An array for fields name that contains the images within we want to work.

````
    bin/console tck:ez:generate-thumbnails [ContentType] [aliases] [array field_names]
    bin/console tck:ez:generate-thumbnails user medium,large profile_picture avatar 
````