import { Page } from '@playwright/test';
import { BasePage } from './BasePage';

export class AdminTranslationPage extends BasePage {
    protected route = 'admin/index/translation';
}