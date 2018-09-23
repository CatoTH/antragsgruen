import {BrowserModule} from '@angular/platform-browser';
import {NgModule} from '@angular/core';
import {HttpClientModule} from "@angular/common/http";
import {AdminIndexComponent} from './admin-index.component';
import {WebsocketService} from "./websocket.service";
import {SelectlistComponent} from "./selectlist.component";

@NgModule({
    declarations: [
        AdminIndexComponent,
        SelectlistComponent,
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
