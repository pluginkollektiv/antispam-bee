# Antispam Bee 3.0

This document is supposed to outline the current approach, so we can discuss it.

## Aims

### Modular approach:
We want to get rid of our monolith `AntispamBee` class and instead have a more modular approach.
What we hope to gain is

1. Those modules (e.g. our filter) could be used from other plugins as well.
2. Other plugins can easily add modules (new filter) to Antispam Bee.

### Testability:
Our monolith `AntispamBee` class can't be unit tested. We move from a Singleton approach to
an approach, we can actually test using PHP Unit.

### Maintainability:
With a more modular approach, we hope to get a software, which is easier to understand, to read
and ultimately to maintain.

## Current status:
The current approach needs PHP7. This enables us to use type hinting and similar advantages of
PHP7. As I guess, ASB3 will need quite a while until release, I could imagine, we will finish up the
version just in the moment PHP7 is the min requirement for WordPress.

Anyway, I would like to continue using PHP7 while developing and if needed to downgrade later on to
e.g. PHP 5.6, as with typehinting we can programme a more robust code, which - if needed - we could
weaken later on.

The current proposal is a running plugin. The following checks are implemented:
* Time
* BBCode
* Country
* DBSpam

In order for you to install this plugin, you also have to do a `composer install`, so the missing vendor
folder gets created and the autoloader works.

## Coding Standards
I tried to follow the WordPress coding standards, except for file naming and I think class naming. There
I follow PSR4 to use a quick autoloader. We can change that if we want.

I would like to emphasize: We should try to have return types and typehints for every function or
method we declare. This might not always be possible, but should be the default.

## Unit tests
So far, I just have converted small pieces. I think I have only written one test to set up the environment.
We should aim for a good code coverage though, but first, I want to have some eyes checking the code
and suggesting improvements to the general structure.

## Architecture

ASB3 basically consists out of `Filter()`. There are two types of filter: `NoSpamFilter()` and
`SpamFilter()`. The first indicate, whether certain `DataInterface` is considered not to be spam,
the second indicates spam.

Each filter needs to be registered. As some filter (see for example see TimeSpam-Filter) need
preparation, this can be done through the `register()` method. For filter which need preparation,
the idea is to create a specific `Preparer` for those filter and hand them over via the filter constructor.
Take for example the TimeSpam-Filter. It needs to prepare another input field. So, we execute the preparer
via `TimeSpam->register()` and this Preparer will now hook into `'comment_form'` to add the according
field. If you wanted to reuse the TimeSpam-Filter in another context (e.g. a contact form), you might need to hook into a
different hook or do a completely different setup. In this case, you do not have to rewrite the whole
filter, you can still reuse the filter but use a different Preparer.

Additionally, a filter returns an `OptionInterface`. This object contains the name and the description of
the filter. It also enables for filter specific configurations. Take as an example the `CountrySpam` filter.
In the settings, the user should be able to configure a country whitelist and a country blacklist.

With the help of the `OptionInterface` the filter can now access these information and we can update those
information in the settings. Unit testing the `options()`-Method seems to be painful though, which indicates a lot
of space for improvements.

Also with the `FilterFactory` I am not too happy yet.

### How does the plugin work
It starts in `'plugins_loaded'` and retrieves all active filter from the repository. First of all, it will
register those, so the Preparer can do their work. 

In the `'pre_comment_approved'` filter, we construct our `CommentData` object, a
`CommentSpamHandler` and a `SpamChecker`. The `SpamChecker` basically runs our `CommentData`
through all active Filter and if those determine, a specific comment is spam, the plugin will
execute the `CommentSpamHandler` and return `'spam'` in the `pre_comment_approved` filter.

### What does the CommentSpamHandler do?
ASB3 saves the spam reason, sends notifications, deletes a spam comment... Basically, depending on
the settings, it executes different tasks, when spam has been detected. The idea of the `CommentSpamHandler`
is, to do exactly this post processing (except for returning `'spam'` in the `pre_comments_approved`-filter).

Therefore we have the PostProcessors. Log spam, delete spam, save spam reason etc. are all supposed to be
single tasks, which `execute()` in the CommentSpamHandler if they are active.

The problem with the PostProcessors: In ASB2 we have a post processor, which basically performs `die()`. This
way, the comment won't be saved. If we keep the PostProcessor architecture, the question is, how to handle
this process. We can't die immediately, because this would mean, in case the logger is active and executed later,
the logger wouldn't be executed.

Two thoughts: We could hook later to die, something like. This is basically the option, I did choose for now in
`RestInPeace`:
```
public function execute(string $reason, DataInterface $data ) : bool {

    return false !== add_action('a-later-hook', function() { die(); };
}
```
but generally I do not like the idea of dying (its hard to unit test `die()`). Unfortunatly, I haven't found 
a nice way to not save the comment and not to die except for 
```
add_filter('query', function($query) { 
    return ( $query !== SaveCommentQuery ) $query : '1=1';
});
```
or something like that. I haven't explored this path yet and what it means for hooks, which are fired further
down the road. But this might be a way to circle around `die()`.

A third option because of the "`RestInPeace`"-PostProcessor could be to to give it another status, to say,
this process is not a PostProcessor. We have basically SpamPostProcessors (which are all executed before "RestInPeace")
and SpamEntityPostProcessors (which would be executed after "RestInPeace" and in case, this "thing" would not be
active). But this adds quite a lot of complexity, which is why I dislike this path.

## Config, Options, Settings
To me, this is currently the least thought through part. A lot of filter and post processor have settings. So, we
need to provide a way to set those settings and the filter needs to be able to access those settings. We need to
design this API in a way third party extensions can be integrated in the ASB settings page in an aligned way.

How is this done? The `Settings\Controller` is currently in charge to render this page. As a dependency it has the
Filter and PostProcessor repositories. Each filter and postprocessor provides an `OptionsInterface`. These `Options`
provide a humanreadable name, a description, and if necessary setting `FieldInterface`. Depending on this object
the `Controller` knows what to render (more precise: _templates/admin/field.php_ as all HTML of the settings area is
placed in template files in an attempt to seperate view from logic). Currently, we only have a `TextField`, but we
could easily extend this and provide a `SelectField`, `MultiSelectField` and so on. Actually, I think the `OptionsInterface`
should also provide the `render` method to gain real flexibility here.

This option provides also the current settings, so the filter or post processor can access them.

The current state is the `Controller` saves in a complicated action all settings in using the `AntispamBeeConfig`
`persist` method. While I think there should be an `AntispamBeeConfig` which stores the information about active
filters and post processors, I think the filter specific settings should be saved using the `OptionInterface` of
the filter and extend this with a `persist()` method. If you compare the `ConfigInterface` and the `OptionInterface`,
you see how similar those are, so I think, the `OptionInterface` actually is a `ConfigInterface+`.

But all this is not properly thought and fleshed out yet. I thought for now, I leave it as it is, as I want to show
you the first scetch of the in my view most important part of the architecture, the filter and post processor
interplay.

## What is missing?
Basically _everything_.

Except for bringing the checks from ASB2 over to ASB3 and fleshing everything out we also need a neat UI/UX.

A problem for me with UX is currently also the `RestInPeace` option. There is no
reason to save the "spam reason" if you do not save the spam comment. In ASB2, when you activate
the feature, to `rest_in_peace`, options, which are only available if you save spam comments disappear in
the settings. This is quite reasonable and would for me be an argument to treat the `RestInPeace`-PostProcessor
on a different level than others (which I rejected above in order for reduced complexity). But maybe, we just
decouple this Javascript feature from the underlying PHP architecture.

We also do have settings, which are outside of the filter/post_processor-flow, namely:
* Do not check trackbacks/pingbacks
* Comment form used outside of posts
* Delete Antispam Bee data when uninstalling

Those need to be integrated into the plugin as well.

*Translateable strings:* I haven't copy&pasted the strings. This would be actually something, we should consider to
save as much translated strings as possible during the transition. This won't be completely possible, I think, as
for example, if you think of the `RestInPeace` as a post processor, its quite something different than the "Save spam
to database" option, so also with a different wording.