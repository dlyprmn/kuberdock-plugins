<div id="all-apps" class="container-fluid content">
    <div class="row">
        <div class="col-md-12">
            <h2>Your Apps</h2>
        </div>
        <div class="col-md-12">
            <p>
                A list of your applications below. Click "Create custom application" to set up new application or click on the name
                of application to go to application page with detailed information of application configuration.
                Use control buttons to start, edit or delete your application.
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="message"></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <table class="table apps-list app-table pod-list">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Access endpoint</th>
                    <th>Pod IP</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <% if (collection.template_id) { %>
                <a href="#" class="add-new-app btn btn-primary pull-right" data-template="<%- collection.template_id %>">Add more apps</a>
            <% } else { %>
                <a href="#" class="add-new-app btn btn-primary pull-right">Create custom application</a>
            <% } %>
            <div class="clearfix"></div>
        </div>
    </div>
</div>