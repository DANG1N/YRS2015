me.Entity.extend({

    /**
     * constructor
     */
    init : function(x, y, settings) {
        this._super(me.Entity, 'init', [ x, y, settings ]);
        this.body.setVelocity(2, 2);
        this.body.gravity = 0;

        // set the display to follow our position on both axis
        me.game.viewport.follow(this.pos, me.game.viewport.AXIS.BOTH);

        this.alwaysUpdate = true;
        this.renderable.addAnimation("walk_down", [ 0, 1, 2, 3, 4, 5, 6, 7 ]);
        this.renderable.addAnimation("walk_up", [ 8, 9, 10, 11, 12, 13, 14, 15 ]);
        this.renderable.addAnimation("walk_left", [ 16, 17, 18, 19, 20, 21, 22, 23 ]);
        this.renderable.addAnimation("walk_right", [ 24, 25, 26, 27, 28, 29, 30, 31 ]);
        this.renderable.addAnimation("stand", [ 0 ]);
        this.renderable.setCurrentAnimation("stand");
    },

    /**
     * update the entity
     */
    update : function(dt) {
        var animation = "stand";
        if (me.input.isKeyPressed('left')) {
            this.body.vel.x -= this.body.accel.x * me.timer.tick;
            animation = "walk_left";
        } else if (me.input.isKeyPressed('right')) {
            this.body.vel.x += this.body.accel.x * me.timer.tick;
            animation = "walk_right";
        } else if (me.input.isKeyPressed('up')) {
            this.body.vel.y -= this.body.accel.y * me.timer.tick;
            animation = "walk_up";
        } else if (me.input.isKeyPressed('down')) {
            this.body.vel.y += this.body.accel.y * me.timer.tick;
            animation = "walk_down";
        } else {
            this.body.vel.x = 0;
            this.body.vel.y = 0;
        }
        if (!this.renderable.isCurrentAnimation(animation)) {
            this.renderable.setCurrentAnimation(animation);
        }
        if (this.body.pos.x < -1) {
            this.body.pos.x = 0;
        }

        // apply physics to the body (this moves the entity)
        this.body.update(dt);

        // handle collisions against other shapes
        me.collision.check(this);

        // return true if we moved or if the renderable was updated
        return (this._super(me.Entity, 'update', [ dt ]) || this.body.vel.x !== 0 || this.body.vel.y !== 0);
    },

    /**
     * colision handler (called when colliding with other objects)
     */
    onCollision : function(response, object) {
        if (object.type == "portal") {
            ScratchLoader.overlayProject(68248124);
            me.state.pause(true);
            return false;
        }
        // Make all other objects solid
        return true;
    }
});
