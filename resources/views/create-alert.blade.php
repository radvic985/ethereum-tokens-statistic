@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info" role="alert">Alerts are a premium feature which we left open for the time being for you to enjoy! If you have any comments, we'd love to <a href="mailto:contact@whalewatchapp.com">hear from you</a>.</div>
            <h1>Create your alert</h1>
            </div>
        </div>
        <form action="/submit" method="post">
            {{ csrf_field() }}
            <div class="step">
                <div class="item-text">
                    <h2>1. Select an example scenario. You will be able to customize your alert in the next step:</h2>
                </div>
                <div class="radio-variants">
                    <ul>
                        <li>
                            <label for="radio1">
                                <input id="radio1" type="radio" name="optradio" value="1" checked>
                                Follow <b>Davenport’s</b> every move, <b>any tokens</b> added or removed worth over $1m
                                USD
                            </label>
                        </li>
                        <li>
                            <label for="radio2"><input id="radio2" type="radio" name="optradio" value="2">
                                <b>Token</b> is added or removed by <b>at least 10%</b> of whales, <b>top 10</b> or <b>all
                                    whales</b>
                            </label>
                        </li>
                        <li>
                            <label for="radio3">
                                <input id="radio3" type="radio" name="optradio" value="3">
                                <b>Rutherford</b> added or removed <b>Token</b> to his portfolio
                            </label>
                        </li>
                        <li>
                            <label for="radio4">
                                <input id="radio4" type="radio" name="optradio" value="4">
                                <b>Kingsley’s</b> portfolio increased by <b>at least 20%</b>
                            </label>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="step">
                <div class="item-text">
                    <h2>2. Select options:</h2>
                    <p>Notify me any time...</p>
                    <div id="option1" class="input-autocomplete">
                        <input type="text" name="whale1" class="autocomplete1" placeholder="Select whale">
                        <span>adds or removes any token worth over
                        <span class="ex">
                            <input type="number" min="50000" name="count1" placeholder="50,000" step="50000">$
                        </span>  to or from their portfolio. </span>
                    </div>

                    <div id="option2" class="input-autocomplete">
                        <input type="text" name="token2" class="autocomplete2" placeholder="Select Token">
                        <span>is <b>added</b> or <b>removed</b> by</span>
                        <div class="radio-variants">
                            <ul>
                                <li>
                                    <label for="radio2-1">
                                        <input id="radio2-1" type="radio" name="step2" value="least">
                                        <span>at least </span>
                                        <input type="number" name="least" class="percent-whale" min="5" max="100"
                                               step="5"
                                               value="5"> %
                                        <span> of all whales</span>
                                    </label>
                                </li>
                                <li>
                                    <label for="radio2-2">
                                        <input id="radio2-2" type="radio" name="step2" value="top">
                                        <span>top </span>
                                        <input type="number" name="top" class="percent-whale" min="10" max="100"
                                               step="5" value="50">
                                        <span> whales</span>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div id="option3" class="input-autocomplete">
                        <input type="text" name="whale3" class="autocomplete1" placeholder="Select for a whale...">
                        <span>added or removed</span>
                        <input type="text" name="token3" class="autocomplete2" placeholder="Select Token">
                        <span> to or from their portfolio. </span>
                    </div>

                    <div id="option4" class="input-autocomplete">
                        <input type="text" name="whale4" class="autocomplete1" placeholder="Select for a whale...">
                        <span>'s portfolio </span>
                        <select name="inc-dec" id="inc-dec">
                            <option value="inc">increased</option>
                            <option value="dec">decreased</option>
                        </select>
                        <span> by at least </span>
                        <input type="number" name="inc-dec-least" min="5" step="5" max="20000" value="20"> %

                    </div>
                </div>

            </div>
            <div class="step">
                <div class="item-text">
                    <h2>3. How would you like to be notified?</h2>
                </div>
                <div class="radio-variants">
                    <ul>
                        <li>
                            <label for="radio5">
                                <input id="radio5" type="radio" name="notificationradio" checked value="1">Email
                            </label>
                        </li>
                        <li>
                            <label for="radio6">
                                <input id="radio6" type="radio" name="notificationradio" disabled value="2">Desktop push
                                notification - Coming Soon...
                            </label>
                        </li>
                        <li>
                            <label for="radio7">
                                <input id="radio7" type="radio" name="notificationradio" disabled value="3">SMS
                                (Premium) - Coming Soon...
                            </label>
                        </li>
                    </ul>
                </div>
            </div>

            <div>
                <input type="submit" name="submit" class="btn create-alert" value="Create Alert">
            </div>
        </form>
    </div>

    <script src="/public/js/create-alert.js"></script>
@endsection
