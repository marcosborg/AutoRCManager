<aside class="main-sidebar">
    <section class="sidebar" style="height: auto;">
        <ul class="sidebar-menu tree" data-widget="tree">
            <li>
                <select class="searchable-field form-control">

                </select>
            </li>
            <li>
                <a href="{{ route("admin.home") }}">
                    <i class="fas fa-fw fa-tachometer-alt">

                    </i>
                    {{ trans('global.dashboard') }}
                </a>
            </li>
            @can('account_access')
                <li class="{{ request()->is("admin/dashboard") ? "active" : "" }}">
                    <a href="{{ route("admin.dashboard") }}">
                        <i class="fa-fw fas fa-chart-line">

                        </i>
                        <span>Visao geral</span>
                    </a>
                </li>
            @endcan
            @can('user_management_access')
                <li class="treeview">
                    <a href="#">
                        <i class="fa-fw fas fa-users">

                        </i>
                        <span>{{ trans('cruds.userManagement.title') }}</span>
                        <span class="pull-right-container"><i class="fa fa-fw fa-angle-left pull-right"></i></span>
                    </a>
                    <ul class="treeview-menu">
                        @can('permission_access')
                            <li class="{{ request()->is("admin/permissions") || request()->is("admin/permissions/*") ? "active" : "" }}">
                                <a href="{{ route("admin.permissions.index") }}">
                                    <i class="fa-fw fas fa-unlock-alt">

                                    </i>
                                    <span>{{ trans('cruds.permission.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('role_access')
                            <li class="{{ request()->is("admin/roles") || request()->is("admin/roles/*") ? "active" : "" }}">
                                <a href="{{ route("admin.roles.index") }}">
                                    <i class="fa-fw fas fa-briefcase">

                                    </i>
                                    <span>{{ trans('cruds.role.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('user_access')
                            <li class="{{ request()->is("admin/users") || request()->is("admin/users/*") ? "active" : "" }}">
                                <a href="{{ route("admin.users.index") }}">
                                    <i class="fa-fw fas fa-user">

                                    </i>
                                    <span>{{ trans('cruds.user.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('audit_log_access')
                            <li class="{{ request()->is("admin/audit-logs") || request()->is("admin/audit-logs/*") ? "active" : "" }}">
                                <a href="{{ route("admin.audit-logs.index") }}">
                                    <i class="fa-fw fas fa-file-alt">

                                    </i>
                                    <span>{{ trans('cruds.auditLog.title') }}</span>

                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan
            @can('setting_access')
                <li class="treeview">
                    <a href="#">
                        <i class="fa-fw fas fa-cogs">

                        </i>
                        <span>{{ trans('cruds.setting.title') }}</span>
                        <span class="pull-right-container"><i class="fa fa-fw fa-angle-left pull-right"></i></span>
                    </a>
                    <ul class="treeview-menu">
                        @can('country_access')
                            <li class="{{ request()->is("admin/countries") || request()->is("admin/countries/*") ? "active" : "" }}">
                                <a href="{{ route("admin.countries.index") }}">
                                    <i class="fa-fw fas fa-flag">

                                    </i>
                                    <span>{{ trans('cruds.country.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('brand_access')
                            <li class="{{ request()->is("admin/brands") || request()->is("admin/brands/*") ? "active" : "" }}">
                                <a href="{{ route("admin.brands.index") }}">
                                    <i class="fa-fw fas fa-circle">

                                    </i>
                                    <span>{{ trans('cruds.brand.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('suplier_access')
                            <li class="{{ request()->is("admin/supliers") || request()->is("admin/supliers/*") ? "active" : "" }}">
                                <a href="{{ route("admin.supliers.index") }}">
                                    <i class="fa-fw fas fa-building">

                                    </i>
                                    <span>{{ trans('cruds.suplier.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('payment_status_access')
                            <li class="{{ request()->is("admin/payment-statuses") || request()->is("admin/payment-statuses/*") ? "active" : "" }}">
                                <a href="{{ route("admin.payment-statuses.index") }}">
                                    <i class="fa-fw far fa-credit-card">

                                    </i>
                                    <span>{{ trans('cruds.paymentStatus.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('carrier_access')
                            <li class="{{ request()->is("admin/carriers") || request()->is("admin/carriers/*") ? "active" : "" }}">
                                <a href="{{ route("admin.carriers.index") }}">
                                    <i class="fa-fw fas fa-truck-moving">

                                    </i>
                                    <span>{{ trans('cruds.carrier.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('pickup_state_access')
                            <li class="{{ request()->is("admin/pickup-states") || request()->is("admin/pickup-states/*") ? "active" : "" }}">
                                <a href="{{ route("admin.pickup-states.index") }}">
                                    <i class="fa-fw fas fa-truck-loading">

                                    </i>
                                    <span>{{ trans('cruds.pickupState.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('repair_state_access')
                            <li class="{{ request()->is("admin/repair-states") || request()->is("admin/repair-states/*") ? "active" : "" }}">
                                <a href="{{ route("admin.repair-states.index") }}">
                                    <i class="fa-fw fas fa-screwdriver">

                                    </i>
                                    <span>{{ trans('cruds.repairState.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('general_state_access')
                            <li class="{{ request()->is("admin/general-states") || request()->is("admin/general-states/*") ? "active" : "" }}">
                                <a href="{{ route("admin.general-states.index") }}">
                                    <i class="fa-fw fas fa-tasks">

                                    </i>
                                    <span>{{ trans('cruds.generalState.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('setting_access')
                            <li class="{{ request()->is("admin/vehicle-state-transfers") ? "active" : "" }}">
                                <a href="{{ route("admin.vehicle-state-transfers.index") }}">
                                    <i class="fa-fw fas fa-history">

                                    </i>
                                    <span>{{ trans('cruds.vehicleStateTransfer.title') }}</span>

                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan
            @can('client_access')
                <li class="{{ request()->is("admin/clients") || request()->is("admin/clients/*") ? "active" : "" }}">
                    <a href="{{ route("admin.clients.index") }}">
                        <i class="fa-fw fas fa-user">

                        </i>
                        <span>{{ trans('cruds.client.title') }}</span>

                    </a>
                </li>
            @endcan
            @can('vehicle_access')
                <li class="{{ request()->is("admin/vehicles") || request()->is("admin/vehicles/*") ? "active" : "" }}">
                    <a href="{{ route("admin.vehicles.index") }}">
                        <i class="fa-fw fas fa-car">

                        </i>
                        <span>{{ trans('cruds.vehicle.title') }}</span>

                    </a>
                </li>
            @endcan
            @can('vehicle_consignment_access')
                <li class="{{ request()->is("admin/vehicle-consignments") || request()->is("admin/vehicle-consignments/*") ? "active" : "" }}">
                    <a href="{{ route("admin.vehicle-consignments.index") }}">
                        <i class="fa-fw fas fa-exchange-alt">

                        </i>
                        <span>{{ trans('cruds.vehicleConsignment.title') }}</span>

                    </a>
                </li>
            @endcan
            @can('vehicle_group_access')
                <li class="{{ request()->is("admin/vehicle-groups") || request()->is("admin/vehicle-groups/*") ? "active" : "" }}">
                    <a href="{{ route("admin.vehicle-groups.index") }}">
                        <i class="fa-fw fas fa-object-group">

                        </i>
                        <span>{{ trans('cruds.vehicleGroup.title') }}</span>

                    </a>
                </li>
            @endcan
            @can('sale_access')
                @foreach (\App\Models\GeneralState::all() as $key => $generalState)
                <li class="{{ request()->is("admin/sales") || request()->is("admin/sales/*") ? "active" : "" }}">
                    <a href="/admin/sales/{{ $generalState->id }}">
                        <i class="fa-fw fas fa-circle">

                        </i>

                        <span>{{ $generalState->name }}</span>

                    </a>
                </li>
                @endforeach
            @endcan
            @can('repair_menu_access')
                <li class="treeview">
                    <a href="#">
                        <i class="fa-fw fas fa-screwdriver">

                        </i>
                        <span>{{ trans('cruds.repairMenu.title') }}</span>
                        <span class="pull-right-container"><i class="fa fa-fw fa-angle-left pull-right"></i></span>
                    </a>
                    <ul class="treeview-menu">
                        @can('repair_access')
                            <li class="{{ request()->is("admin/repairs") || request()->is("admin/repairs/*") ? "active" : "" }}">
                                <a href="{{ route("admin.repairs.index") }}">
                                    <i class="fa-fw fas fa-car">

                                    </i>
                                    <span>{{ trans('cruds.repair.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('repair_access')
                            <li class="{{ request()->is("admin/supplier-orders") || request()->is("admin/supplier-orders/*") ? "active" : "" }}">
                                <a href="{{ route("admin.supplier-orders.index") }}">
                                    <i class="fa-fw fas fa-clipboard-list">

                                    </i>
                                    <span>{{ trans('cruds.supplierOrder.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('create_car_for_repair_access')
                            <li class="{{ request()->is("admin/create-car-for-repairs") || request()->is("admin/create-car-for-repairs/*") ? "active" : "" }}">
                                <a href="{{ route("admin.create-car-for-repairs.index") }}">
                                    <i class="fa-fw fas fa-plus">

                                    </i>
                                    <span>{{ trans('cruds.createCarForRepair.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('timelog_access')
                            <li class="{{ request()->is("admin/timelogs") || request()->is("admin/timelogs/*") ? "active" : "" }}">
                                <a href="{{ route("admin.timelogs.index") }}">
                                    <i class="fa-fw fas fa-clock">

                                    </i>
                                    <span>{{ trans('cruds.timelog.title') }}</span>

                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan
            @can('account_access')
                <li class="treeview">
                    <a href="#">
                        <i class="fa-fw fas fa-euro-sign">

                        </i>
                        <span>{{ trans('cruds.account.title') }}</span>
                        <span class="pull-right-container"><i class="fa fa-fw fa-angle-left pull-right"></i></span>
                    </a>
                    <ul class="treeview-menu">
                        @can('account_operation_access')
                            <li class="{{ request()->is("admin/account-operations") || request()->is("admin/account-operations/*") ? "active" : "" }}">
                                <a href="{{ route("admin.account-operations.index") }}">
                                    <i class="fa-fw fas fa-plus">

                                    </i>
                                    <span>{{ trans('cruds.accountOperation.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('account_access')
                            <li class="{{ request()->is("admin/reports/operational-units") ? "active" : "" }}">
                                <a href="{{ route("admin.reports.operational-units") }}">
                                    <i class="fa-fw fas fa-clipboard-list">

                                    </i>
                                    <span>Relatorio unidades</span>

                                </a>
                            </li>
                        @endcan
                        @can('client_ledger_entry_access')
                            <li class="{{ request()->is("admin/client-ledger-entries") || request()->is("admin/client-ledger-entries/*") ? "active" : "" }}">
                                <a href="{{ route("admin.client-ledger-entries.index") }}">
                                    <i class="fa-fw fas fa-book">

                                    </i>
                                    <span>{{ trans('cruds.clientLedgerEntry.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('vehicle_financial_entry_access')
                            <li class="{{ request()->is("admin/vehicle-financial-entries") || request()->is("admin/vehicle-financial-entries/*") ? "active" : "" }}">
                                <a href="{{ route("admin.vehicle-financial-entries.index") }}">
                                    <i class="fa-fw fas fa-receipt">

                                    </i>
                                    <span>{{ trans('cruds.vehicleFinancialEntry.title') }}</span>

                                </a>
                            </li>
                        @endcan
                        @can('account_configuration_access')
                            <li class="treeview">
                                <a href="#">
                                    <i class="fa-fw fas fa-cogs">

                                    </i>
                                    <span>{{ trans('cruds.accountConfiguration.title') }}</span>
                                    <span class="pull-right-container"><i class="fa fa-fw fa-angle-left pull-right"></i></span>
                                </a>
                                <ul class="treeview-menu">
                                    @can('account_department_access')
                                        <li class="{{ request()->is("admin/account-departments") || request()->is("admin/account-departments/*") ? "active" : "" }}">
                                            <a href="{{ route("admin.account-departments.index") }}">
                                                <i class="fa-fw far fa-building">

                                                </i>
                                                <span>{{ trans('cruds.accountDepartment.title') }}</span>

                                            </a>
                                        </li>
                                    @endcan
                                    @can('account_category_access')
                                        <li class="{{ request()->is("admin/account-categories") || request()->is("admin/account-categories/*") ? "active" : "" }}">
                                            <a href="{{ route("admin.account-categories.index") }}">
                                                <i class="fa-fw fas fa-th-large">

                                                </i>
                                                <span>{{ trans('cruds.accountCategory.title') }}</span>

                                            </a>
                                        </li>
                                    @endcan
                                    @can('account_item_access')
                                        <li class="{{ request()->is("admin/account-items") || request()->is("admin/account-items/*") ? "active" : "" }}">
                                            <a href="{{ route("admin.account-items.index") }}">
                                                <i class="fa-fw far fa-square">

                                                </i>
                                                <span>{{ trans('cruds.accountItem.title') }}</span>

                                            </a>
                                        </li>
                                    @endcan
                                    @can('payment_method_access')
                                        <li class="{{ request()->is("admin/payment-methods") || request()->is("admin/payment-methods/*") ? "active" : "" }}">
                                            <a href="{{ route("admin.payment-methods.index") }}">
                                                <i class="fa-fw far fa-credit-card">

                                                </i>
                                                <span>{{ trans('cruds.paymentMethod.title') }}</span>

                                            </a>
                                        </li>
                                    @endcan
                                </ul>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan
            @can('depreciation_access')
                <li class="{{ request()->is("admin/depreciations") || request()->is("admin/depreciations/*") ? "active" : "" }}">
                    <a href="{{ route("admin.depreciations.index") }}">
                        <i class="fa-fw fas fa-arrow-circle-down">

                        </i>
                        <span>{{ trans('cruds.depreciation.title') }}</span>

                    </a>
                </li>
            @endcan
            @can('appreciation_access')
                <li class="{{ request()->is("admin/appreciations") || request()->is("admin/appreciations/*") ? "active" : "" }}">
                    <a href="{{ route("admin.appreciations.index") }}">
                        <i class="fa-fw fas fa-arrow-circle-up">

                        </i>
                        <span>{{ trans('cruds.appreciation.title') }}</span>

                    </a>
                </li>
            @endcan
            <li class="{{ request()->is("admin/system-calendar") || request()->is("admin/system-calendar/*") ? "active" : "" }}">
                <a href="{{ route("admin.systemCalendar") }}">
                    <i class="fas fa-fw fa-calendar">

                    </i>
                    <span>{{ trans('global.systemCalendar') }}</span>
                </a>
            </li>
            @php($unread = \App\Models\QaTopic::unreadCount())
                <li class="{{ request()->is("admin/messenger") || request()->is("admin/messenger/*") ? "active" : "" }}">
                    <a href="{{ route("admin.messenger.index") }}">
                        <i class="fa-fw fa fa-envelope">

                        </i>
                        <span>{{ trans('global.messages') }}</span>
                        @if($unread > 0)
                            <strong>( {{ $unread }} )</strong>
                        @endif

                    </a>
                </li>
                @if(file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php')))
                    @can('profile_password_edit')
                        <li class="{{ request()->is('profile/password') || request()->is('profile/password/*') ? 'active' : '' }}">
                            <a href="{{ route('profile.password.edit') }}">
                                <i class="fa-fw fas fa-key">
                                </i>
                                {{ trans('global.change_password') }}
                            </a>
                        </li>
                    @endcan
                @endif
                <li>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logoutform').submit();">
                        <i class="fas fa-fw fa-sign-out-alt">

                        </i>
                        {{ trans('global.logout') }}
                    </a>
                </li>
        </ul>
    </section>
</aside>
