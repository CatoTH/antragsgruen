import { Page } from '@playwright/test';
import { BasePage } from './BasePage';

export class AdminAdminConsultationsPage extends BasePage {
    protected route = 'admin/index/siteconsultations';
}