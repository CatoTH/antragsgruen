declare let thruway: any;
declare let Observable: any;

export class Client {
    private static singletonObject: Client = null;

    public static getInstance(key: string) {
        if (Client.singletonObject == null) {
            Client.singletonObject = new Client(key);
        }
        return Client.singletonObject;
    }

    protected wamp: any;

    constructor(key: string) {
        console.log(key);
        this.wamp = new thruway.Client('ws://localhost:9090', 'antragsgruen', {
            authmethods: ['jwt']
        });
        this.wamp.onChallenge(function(challenge, method) {
            console.log("1", Observable);
            let observ = Observable.create((observer) => {
                console.log("!");
                challenge.subscribe((msg) => {
                    if (msg.authMethod == 'jwt') {
                        console.log(msg, "Returning ", key);
                        observer.next(key);
                    }
                });
            });
            return observ.subscribe();
        });
        this.wamp.topic('example.topic').subscribe((v) => console.log(v));
    }

    public getMotion(id) {
        return this.wamp.call('antragsgruen.rpc.getMotion', [id])
            .map((r: any) => JSON.parse(r['args'][0]));
    }
}
