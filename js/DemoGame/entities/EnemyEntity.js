/* --------------------------
 an enemy Entity
 ------------------------ */
me.Entity
        .extend({
            init : function(x, y, settings) {
                // define this here instead of tiled
                settings.image = "wheelie_right";

                // save the area size defined in Tiled
                var width = settings.width;
                var height = settings.height;

                // adjust the size setting information to match the sprite size
                // so that the entity object is created with the right size
                settings.framewidth = settings.width = 64;
                settings.frameheight = settings.height = 64;

                // redefine the default shape (used to define path) with a shape
                // matching the renderable
                settings.shapes[0] = new me.Rect(0, 0, settings.framewidth, settings.frameheight);

                // call the parent constructor
                this._super(me.Entity, 'init', [ x, y, settings ]);

                // set start/end position based on the initial area size
                x = this.pos.x;
                this.startX = x;
                this.endX = x + width - settings.framewidth
                this.pos.x = x + width - settings.framewidth;

                // to remember which side we were walking
                this.walkLeft = false;

                // walking & jumping speed
                this.body.setVelocity(4, 6);

            },

            // manage the enemy movement
            update : function(dt) {

                if (this.alive) {
                    if (this.walkLeft && this.pos.x <= this.startX) {
                        this.walkLeft = false;
                    } else if (!this.walkLeft && this.pos.x >= this.endX) {
                        this.walkLeft = true;
                    }
                    // make it walk
                    this.renderable.flipX(this.walkLeft);
                    this.body.vel.x += (this.walkLeft) ? -this.body.accel.x * me.timer.tick : this.body.accel.x
                            * me.timer.tick;

                } else {
                    this.body.vel.x = 0;
                }

                // update the body movement
                this.body.update(dt);

                // handle collisions against other shapes
                me.collision.check(this);

                // return true if we moved or if the renderable was updated
                return (this._super(me.Entity, 'update', [ dt ]) || this.body.vel.x !== 0 || this.body.vel.y !== 0);
            },

            /**
             * colision handler (called when colliding with other objects)
             */
            onCollision : function(response, other) {
                if (response.b.body.collisionType !== me.collision.types.WORLD_SHAPE) {
                    // res.y >0 means touched by something on the bottom
                    // which mean at top position for this one
                    if (this.alive && (response.overlapV.y > 0) && response.a.body.falling) {
                        this.renderable.flicker(750);
                    }
                    return false;
                }
                // Make all other objects solid
                return true;
            }
        });
