import {BrowserModule} from '@angular/platform-browser';
import {NgModule} from '@angular/core';
import {HttpClientModule} from "@angular/common/http";
import {AdminIndexComponent} from './admin-index.component';
import {WebsocketService} from "./websocket.service";

@NgModule({
    declarations: [
        AdminIndexComponent,
    ],
    imports: [
        BrowserModule,
        HttpClientModule,
    ],
    providers: [
        WebsocketService
    ],
    bootstrap: [AdminIndexComponent]
})
export class AppModule {
}
