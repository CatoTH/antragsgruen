declare let thruway: any;

export class Client {
    private static singletonObject: Client = null;

    public static getInstance() {
        if (Client.singletonObject == null) {
            Client.singletonObject = new Client();
        }
        return Client.singletonObject;
    }

    protected wamp: any;

    constructor() {
        this.wamp = new thruway.Client('ws://localhost:9090', 'realm1');
        this.wamp.topic('example.topic').subscribe((v)=>console.log(v));
    }
}
