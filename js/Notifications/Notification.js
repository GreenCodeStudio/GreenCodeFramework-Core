export class Notification {
    constructor(message, link = null, stamp = null, expires = null) {
        if (stamp == null) stamp = new Date();
        this.message = message;
        this.link = link;
        this.stamp = stamp;
        this.expires = expires;
    }

}