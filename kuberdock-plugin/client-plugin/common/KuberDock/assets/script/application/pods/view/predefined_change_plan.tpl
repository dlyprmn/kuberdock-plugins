<div class="container-fluid content preapp-plan-page">
    <div class="row">
        <div class="kd-page-header clearfix">
            <div class="col-xs-1 nopadding"><span class="header-ico-preapp-install"></span></div>
            <h2 class="col-xs-11">Change plan for <%= templateModel.getName() %></h2>
        </div>
        <div class="col-xs-11 col-xs-offset-1 nopadding">
            <p class="pre-app-desc"><%= templateModel.getPreDescription() %></p>
        </div>
    </div>

    <form class="form-horizontal container-install predefined plans" method="post">
        <div class="col-xs-11 col-xs-offset-1 nopadding">
            <div class="row">
                <strong>Choose package:</strong><br/><br/>
            </div>

            <div class="row col-xs-12 nopadding plans-area centered">
            <% _.each(templateModel.get('plans'), function (plan, k) { %>
                <% if(plan.recommended) { %>
                <div class="col-md-3 col-sm-6 col-xs-12" >
                    <div class="item recommended">
                        <span class="title">recommended</span>
                <% } else { %>
                <div class="col-md-3 col-sm-6 col-xs-12">
                    <div class="item">
                <% } %>
                        <div class="img-wrapper">
                            <span class="plan-name"><%- plan.name %></span>
                            <div class="price-wrapper">
                                <div class="price">
                                    <%- plan.info.prefix %> <%- plan.info.price.toFixed(2) %><wbr>
                                    <span><%- plan.info.suffix %> / <%- plan.info.period %></span>
                                </div>
                            </div>
                        </div>
                        <div class="description">
                            <strong>Good for</strong>
                            <span><%- plan.goodFor %></span>
                        </div>
                        <div class="text-center">
                            <a class="show-details rotate">Show details</a>
                            <%= planDescription(k) %>
                        </div>
                        <div class="margin-top">
                        <% if (plan.name == model.get('template_plan_name')) { %>
                            <a class="btn btn-primary select-plan current-plan">
                                Current package
                            </a>
                        <% } else { %>
                            <a class="btn btn-primary select-plan" data-plan="<%- k %>">
                                Choose package
                            </a>
                        <% } %>
                        </div>
                    </div>
                </div>
            <% }); %>
            </div>
        </div>
    </form>

    <div class="row col-xs-12 info-description">You can choose another package at any time</div>
</div>