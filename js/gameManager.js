var GameRegistry = {};
(function() {

    var Game = function(namespace) {
        var self = this;
        function named(name) {
            return namespace + "." + name;
        }
        var ready = false;
        this.setReadyOrLoad = function() {
            self.loadWhenReady();
            ready = true;
        };
        this.loadWhenReady = function() {
            if (ready) {
                this.onLoad();
                this.onLoad = function() {
                };
            }
        };
        this.onLoad = function() {
        };
        this.pool = new function() {
            this.register = function(className, entityObj, pooling) {
                me.pool.register(named(className), entityObj, pooling);
            };
        };
        this.data = {};
        this.screenRegistry = {};
        this.entityRegistry = {};

        this.levelDirector = new function() {
            this.loadLevel = function(name) {
                console.debug("Loading level: ", name);
                var fqn = named(name);
                var tmx = me.loader.getTMX(fqn);
                if (tmx && tmx.tileset) {
                    var loaded = 0;
                    for (var i = 0, len = tmx.tileset.length; i < len; i++) {
                        var tSet = tmx.tileset[i];
                        tSet.image.source = "assets/" + namespace + "/maps/{random}/" + tSet.image.source;
                        me.loader.load({
                            'name' : tSet.name,
                            'src' : tSet.image.source,
                            'type' : 'image'
                        }, function() {
                            if (++loaded == len) {
                                me.levelDirector.loadLevel(fqn);
                            }
                        });
                    }
                }
            };
        };
    };

    var games = {};

    var GameBuilder = function(namespace) {
        var resources = [];
        var loadingEntities = [];
        var loadingScreens = [];

        function loadClass(fqn, callback) {
            ajax.get("js/" + namespace + "/" + fqn + ".js", function(javaScript) {
                var classObj = eval(javaScript);
                callback(fqn, classObj);
            });
        }
        function loadClasses(root, names, callback) {
            for (var i = 0, len = names.length; i < len; i++) {
                loadClass(root + "/" + names[i], callback);
            }
        }

        this.maps = function(maps) {
            for (var i = 0; i < maps.length; i++) {
                resources.push({
                    'name' : namespace + "." + maps[i],
                    'type' : 'tmx',
                    'src' : "assets/" + namespace + "/maps/" + maps[i] + "/" + maps[i] + ".tmx"
                });
            }
            return this;
        };

        this.entities = function(entities) {
            loadingEntities = entities;
            return this;
        };

        this.screens = function(screens) {
            loadingScreens = screens;
            return this;
        };

        this.sprites = function(sprites) {
            for (var i = 0; i < sprites.length; i++) {
                resources.push({
                    'name' : sprites[i], // namespace + "." + sprites[i],
                    'type' : 'image',
                    'src' : "assets/" + namespace + "/sprites/" + sprites[i] + ".png"
                });
            }
            return this;
        };

        this.build = function() {
            me.loader.preload(resources);
            var game = new Game(namespace);
            var remaining = loadingEntities.length + loadingScreens.length;
            function loadWhenReady() {
                if (remaining == 0) {
                    game.setReadyOrLoad();
                    remaining = -1;
                }
            }
            loadWhenReady();
            function tickDown() {
                remaining--;
                loadWhenReady();
            }
            loadClasses('entities', loadingEntities, function(fqn, entity) {
                game.entityRegistry[fqn.substring(fqn.lastIndexOf("/") + 1)] = entity;
                tickDown();
            });
            loadClasses('screens', loadingScreens, function(fqn, screen) {
                game.screenRegistry[fqn.substring(fqn.lastIndexOf("/") + 1)] = screen;
                tickDown();
            });
            games[namespace] = game;
            return game;
        };

        return this;
    };

    GameRegistry.gameBuilder = function(namespace) {
        return new GameBuilder(namespace);
    };

    GameRegistry.initializeGame = function(name) {
        console.debug("Initializing game", name);
        var script = document.createElement('script');
        script.src = 'js/' + name + '/game.js';
        document.head.appendChild(script);
    };

    GameRegistry.getGame = function(name) {
        return games[name];
    };
})();
