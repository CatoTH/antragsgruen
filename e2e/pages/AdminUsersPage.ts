import { Page } from '@playwright/test';
import { BasePage } from './BasePage';

export class AdminUsersPage extends BasePage {
    protected route = 'admin/users/index';
}