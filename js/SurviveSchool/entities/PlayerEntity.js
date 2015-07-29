me.Entity.extend({

    /**
     * constructor
     */
    init : function(x, y, settings) {
        settings.image = settings.image || 'gripe_run_right';
        this._super(me.Entity, 'init', [ x, y, settings ]);
        this.body.setVelocity(2, 2);
        this.body.gravity = 0

        // set the display to follow our position on both axis
        me.game.viewport.follow(this.pos, me.game.viewport.AXIS.BOTH);

        // ensure the player is updated even when outside of the viewport
        this.alwaysUpdate = true;
        // define a basic walking animation (using all frames)
        this.renderable.addAnimation("walk", [ 0, 1, 2, 3, 4, 5, 6, 7 ]);
        // define a standing animation (using the first frame)
        this.renderable.addAnimation("stand", [ 0 ]);
        // set the standing animation as default
        this.renderable.setCurrentAnimation("stand");
    },

    /**
     * update the entity
     */
    update : function(dt) {
        if (me.input.isKeyPressed('left')) {
            // flip the sprite on horizontal axis
            this.renderable.flipX(true);
            // update the entity velocity
            this.body.vel.x -= this.body.accel.x * me.timer.tick;
            // change to the walking animation
            if (!this.renderable.isCurrentAnimation("walk")) {
                this.renderable.setCurrentAnimation("walk");
            }
        } else if (me.input.isKeyPressed('right')) {
            // unflip the sprite
            this.renderable.flipX(false);
            // update the entity velocity
            this.body.vel.x += this.body.accel.x * me.timer.tick;
            // change to the walking animation
            if (!this.renderable.isCurrentAnimation("walk")) {
                this.renderable.setCurrentAnimation("walk");
            }
        } else if (me.input.isKeyPressed('up')) {
            this.renderable.flipY(false);
            // update the entity velocity
            this.body.vel.y -= this.body.accel.y * me.timer.tick;
        } else if (me.input.isKeyPressed('down')) {
            this.renderable.flipY(true);
            // update the entity velocity
            this.body.vel.y += this.body.accel.y * me.timer.tick;
        } else {
            this.body.vel.x = 0;
            this.body.vel.y = 0;
            // change to the standing animation
            this.renderable.setCurrentAnimation("stand");
        }
        window.playerE = this
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
        if (object.type  == "portal") {
            ScratchLoader.overlayProject(68248124)
            me.state.pause(true)
            return false;
        }
        // Make all other objects solid
        return true;
    }
});
