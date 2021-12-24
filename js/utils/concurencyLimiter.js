export class ConcurencyLimiter {
    constructor(limit = 3) {
        this.limit = 3;
        this.runningCount = 0;
        this.waiting = new Set();
    }

    async run(fun) {
        if (this.runningCount < this.limit) {
            this.runningCount++;
            try {
                return await fun()
            } finally {
                this.runningCount--;
                this.tryRunWaiting()
            }
        } else {
            return await new Promise(r => {
                const waitingFunc = async () => {
                    if (this.runningCount < this.limit) {
                        this.runningCount++;
                        this.waiting.delete(waitingFunc);
                        try {
                            const response = await fun();
                            r(response);
                        } finally {
                            this.runningCount--;
                            this.tryRunWaiting()
                        }
                    }
                }
                this.waiting.add(waitingFunc);
            })
        }
    }

    tryRunWaiting() {
        while (this.runningCount < this.limit && this.waiting.size > 0) {
            this.waiting.values().next().value();
        }
    }
}