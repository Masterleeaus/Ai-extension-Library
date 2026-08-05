import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/controller/auth/registration/registration_controller.dart';

import '../../../backend/local_storage/local_storage.dart';
import '../../../backend/model/auth/registation/basic_data_model.dart';
import '../../../backend/model/auth/registation/registration_with_kyc_model.dart';
import '../../../backend/services/api_services.dart';
import '../../../backend/utils/logger.dart';
import '../../../language/english.dart';
import '../../../model/id_type_model.dart';
import '../../../routes/routes.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';
import '../../../utils/size.dart';
import '../../../widgets/inputs/primary_input_filed.dart';
import '../../../widgets/others/update_kyc_widget/kyc_image_widget.dart';
import '../../../widgets/payment_link/custom_drop_down.dart';
import '../../../widgets/text_labels/title_heading4_widget.dart';

final log = logger(BasicDataController);

class BasicDataController extends GetxController {
  // kyc from field
  final firstNameController = TextEditingController();
  final lastNameController = TextEditingController();
  final emailController = TextEditingController();
  final phoneNumberController = TextEditingController();
  final countryCodeController = TextEditingController();
  final countryController = TextEditingController();
  final cityController = TextEditingController();
  final zipCodeController = TextEditingController();
  final frontPartController = TextEditingController();
  final backPartController = TextEditingController();
  final passwordController = TextEditingController();
  final confirmPasswordController = TextEditingController();

  @override
  void dispose() {
    firstNameController.dispose();
    lastNameController.dispose();
    emailController.dispose();
    phoneNumberController.dispose();
    countryCodeController.dispose();
    cityController.dispose();
    zipCodeController.dispose();
    frontPartController.dispose();
    backPartController.dispose();
    passwordController.dispose();
    confirmPasswordController.dispose();
    inputFieldControllers.clear();
    super.dispose();
  }

  // import controller
  final controller = Get.put(RegistrationController());

  // value
  RxString countryCode = LocalStorages.getCountryCode()!.obs;
  RxString countryName = "".obs;
  RxBool termsAndCondition = false.obs;

  List<TextEditingController> inputFieldControllers = [];
  List<String> listImagePath = [];
  List<String> listFieldName = [];
  String translatedFieldName = '';

  RxList inputFileFields = [].obs;
  RxList inputFields = [].obs;
  RxBool hasFile = false.obs;

  final selectedIDType = "".obs;
  List<IdTypeModel> idTypeList = [];

  @override
  void onInit() {
    countryCodeController.text = LocalStorages.getCountry()!;
    countryController.text = LocalStorages.getCountry()!;
    getBasicData();
    super.onInit();
  }

  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;

  late BasicDataModel _basicDataModel;

  BasicDataModel get basicDataModel => _basicDataModel;

  Future<BasicDataModel> getBasicData() async {
    inputFields.clear();
    inputFieldControllers.clear();
    _isLoading.value = true;
    update();

    await ApiServices.basicData().then((value) {
      _basicDataModel = value!;
      final data = _basicDataModel.data.registerKycFields!.fields;

      LocalStorages.saveEmailVerification(
          isEmailVerification: _basicDataModel.data.emailVerification);
      LocalStorages.saveKycVerification(
          isKycVerification: _basicDataModel.data.kycVerification);

      LocalStorages.saveCountryCode(
          countryCodeValue:
              _basicDataModel.data.countries.first.mobileCode.toString());
      LocalStorages.saveCountry(
          countryValue: _basicDataModel.data.countries.first.name.toString());
      countryController.text =
          _basicDataModel.data.countries.first.name.toString();

      if (LocalStorages.isKycVerification()) {
        for (int item = 0; item < data.length; item++) {
          // make the dynamic controller
          var textEditingController = TextEditingController();
          inputFieldControllers.add(textEditingController);

          // make dynamic input widget
          if (data[item].type.contains('file')) {
            hasFile.value = true;
            inputFileFields.add(
              Column(
                crossAxisAlignment: crossStart,
                children: [
                  TitleHeading4Widget(
                    text: data[item].label,
                    textAlign: TextAlign.left,
                    color: CustomColor.primaryLightTextColor,
                    fontSize: Dimensions.headingTextSize3,
                    fontWeight: FontWeight.w600,
                  ),
                  verticalSpace(Dimensions.heightSize),
                  KycImageWidget(
                    labelName: data[item].label,
                    fieldName: data[item].name,
                  ),
                ],
              ),
            );
          } else if (data[item].type.contains('text') ||
              data[item].type.contains('textarea')) {
            inputFields.add(
              Column(
                children: [
                  verticalSpace(Dimensions.heightSize),
                  PrimaryInputWidget(
                    paddings: EdgeInsets.only(
                      left: Dimensions.widthSize,
                      right: Dimensions.widthSize,
                      bottom: Dimensions.heightSize,
                    ),
                    controller: inputFieldControllers[item],
                    hint: data[item].label,
                    isValidator: data[item].required,
                    label: data[item].label,
                  ),
                ],
              ),
            );
          }
          // final selectedIDType = "".obs;
          // List<IdTypeModel> idTypeList = [];
          else if (data[item].type.contains('select')) {
            hasFile.value = true;
            selectedIDType.value =
                data[item].validation.options.first.toString();
            inputFieldControllers[item].text = selectedIDType.value;
            for (var element in data[item].validation.options) {
              idTypeList.add(IdTypeModel(element, element));
            }
            inputFields.add(
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Obx(() => CustomDropDown<IdTypeModel>(
                      items: idTypeList,
                      title: data[item].label,
                      hint: selectedIDType.value.isEmpty
                          ? Strings.selectType
                          : selectedIDType.value,
                      onChanged: (value) {
                        selectedIDType.value = value!.title;
                      },
                      padding: EdgeInsets.symmetric(
                        horizontal: Dimensions.paddingHorizontalSize * 0.25,
                      ),
                      titleTextColor:
                          CustomColor.primaryLightTextColor.withOpacity(.2),
                      borderEnable: true,
                      dropDownFieldColor: Colors.transparent,
                      dropDownIconColor:
                          CustomColor.primaryLightTextColor.withOpacity(.2))),
                  verticalSpace(Dimensions.marginBetweenInputBox * .8),
                ],
              ),
            );
          }
        }
      }

      _isLoading.value = false;
      update();
    }).catchError(
      (onError) {
        log.e(onError);
      },
    );
    update();
    return _basicDataModel;
  }

  //
  late RegistrationWithKycModel _registrationModel;

  RegistrationWithKycModel get registrationModel => _registrationModel;

  Future<RegistrationWithKycModel> registrationProcess() async {
    _isLoading.value = true;
    String isAgree = '';
    if (termsAndCondition.value) {
      isAgree = '1';
    } else {
      isAgree = '';
    }
    Map<String, String> inputBody = {
      'firstname': firstNameController.text,
      'lastname': lastNameController.text,
      'email': controller.emailController.text,
      'phone_code': countryCode.value,
      'phone': phoneNumberController.text,
      'country': countryController.text,
      'city': cityController.text,
      'zip_code': zipCodeController.text,
      'password': passwordController.text,
      'password_confirmation': confirmPasswordController.text,
      'agree': isAgree.toString(),
    };
    final data = _basicDataModel.data.registerKycFields!.fields;

    for (int i = 0; i < data.length; i += 1) {
      if (data[i].type != 'file') {
        inputBody[data[i].name] = inputFieldControllers[i].text;  
      }
    }

    await ApiServices.registrationApi(
            body: inputBody, fieldList: listFieldName, pathList: listImagePath)
        .then((value) {
      _registrationModel = value!;   
      _isLoading.value = false;
      update();

      _goToSavedUser(_registrationModel);
    }).catchError((onError) {
      log.e(onError);
    });
    _isLoading.value = false;
    update();
    return _registrationModel;
  }

  _goToSavedUser(RegistrationWithKycModel loginModel) {
    LocalStorages.saveToken(token: loginModel.data.token.toString());
    LocalStorages.isLoginSuccess(isLoggedIn: true);
    LocalStorages.isLoggedIn();
    update();
    _goToDashboardScreen();
  }

  _goToDashboardScreen() {
    Get.offAndToNamed(Routes.waitForApprovalScreen);
  }

  updateImageData(String fieldName, String imagePath) {
    if (listFieldName.contains(fieldName)) {
      int itemIndex = listFieldName.indexOf(fieldName);
      listImagePath[itemIndex] = imagePath;
    } else {
      listFieldName.add(fieldName);
      listImagePath.add(imagePath);
    }
    update();
  }

  String? getImagePath(String fieldName) {
    if (listFieldName.contains(fieldName)) {
      int itemIndex = listFieldName.indexOf(fieldName);
      return listImagePath[itemIndex];
    }
    return null;
  }
}
