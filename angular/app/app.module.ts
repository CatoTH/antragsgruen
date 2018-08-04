import {BrowserModule} from '@angular/platform-browser';
import {NgModule} from '@angular/core';

import {AdminIndexComponent} from './admin-index.component';
import {WebsocketService} from "./websocket.service";

@NgModule({
    declarations: [
        AdminIndexComponent
    ],
    imports: [
        BrowserModule
    ],
    providers: [
        WebsocketService
    ],
    bootstrap: [AdminIndexComponent]
})
export class AppModule {
}
